<?php

namespace App\Helper;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use Exception;


/**
 * Class OfficeHelper
 * @package App\Helper
 */
class OfficeHelper
{
    /**
     * Support UTF-8
     */
    const BOM_UTF8 = "\xEF\xBB\xBF";

    /**
     * Number of rows to write before flushing
     */
    const FLUSH_THRESHOLD = 500;

    /**
     * @var string Path to the output file
     */
    protected $outputFilePath;

    /**
     * @var resource Pointer to the file/stream we will write to
     */
    protected $filePointer;

    /**
     * @var bool Indicates whether the writer has been opened or not
     */
    protected $isWriterOpened = false;

    /**
     * @var string Defines the character used to delimit fields (one character only)
     */
    protected $delimiter = ',';

    /**
     * @var string Defines the character used to enclose fields (one character only)
     */
    protected $enclosure = '"';

    /**
     * @var int
     */
    protected $lastWrittenRowIndex = 0;

    /**
     * OfficeHelper constructor.
     */
    public function __construct()
    {

    }

    /**
     * Setter
     *
     * @param string $delimiter
     */
    public function setDelimiter(string $delimiter = ',')
    {
        $this->delimiter = $delimiter;
    }

    /**
     * Setter
     *
     * @param string $enclosure
     */
    public function setEnclosure(string $enclosure = '"')
    {
        $this->enclosure = $enclosure;
    }

    /**
     * Inits the writer and opens it to accept data.
     * By using this method, the data will be written to a file.
     *
     * @param string $outputFilePath Path of the output file that will contain the data
     * @param bool   $appended       Whether to append content to the end of the file
     *
     * @return $this
     * @throws Exception If the writer cannot be opened or if the given path is not writable
     */
    public function openToFile(string $outputFilePath, bool $appended = false): self
    {
        $this->outputFilePath = $outputFilePath;

        $this->filePointer = fopen($this->outputFilePath, $appended ? 'ab+' : 'wb+');

        if (!$this->filePointer) {
            throw new Exception('File pointer has not be opened');
        }

        fputs($this->filePointer, static::BOM_UTF8);

        $this->isWriterOpened = true;

        return $this;
    }

    /**
     * Write given data to the output. New data will be appended to end of stream.
     *
     * @param array $dataRows Array of array containing data to be streamed.
     *                        If a row is empty, it won't be added (i.e. not even as a blank row)
     *                        Example: $dataRows = [
     *                        ['data11', 12, , '', 'data13'],
     *                        ['data21', 'data22', null, false],
     *                        ];
     *
     * @return $this
     * @throws Exception If this function is called before opening the writer
     * @throws Exception If unable to write data
     * @throws Exception If the input param is not valid
     */
    public function addRows(array $dataRows) :self
    {
        if (!empty($dataRows)) {
            $firstRow = reset($dataRows);
            if (!is_array($firstRow)) {
                throw new Exception('The input should be an array of arrays');
            }

            foreach ($dataRows as $dataRow) {
                $this->addRow($dataRow);
            }
        }

        return $this;
    }

    /**
     * Write given data to the output. New data will be appended to end of stream.
     *
     * @param array $dataRow Array containing data to be streamed.
     *                       If empty, no data is added (i.e. not even as a blank row)
     *                       Example: $dataRow = ['data1', 1234, null, '', 'data5', false];
     *
     * @return $this
     * @throws Exception If unable to write data
     * @throws Exception If anything else goes wrong while writing data
     * @throws Exception If this function is called before opening the writer
     */
    public function addRow(array $dataRow) :self
    {
        if ($this->isWriterOpened) {
            // empty $dataRow should not add an empty line
            if (!empty($dataRow)) {
                try {
                    $this->addRowToWriter($dataRow);
                } catch (Exception $e) {
                    // if an exception occurs while writing data,
                    // close the writer and remove all files created so far.
                    $this->closeAndAttemptToCleanupAllFiles();

                    // re-throw the exception to alert developers of the error
                    throw $e;
                }
            }
        } else {
            throw new Exception('The writer needs to be opened before adding row.');
        }

        return $this;
    }

    /**
     * Adds data to the currently opened writer.
     *
     * @param array $dataRow Array containing data to be written.
     *                       Example $dataRow = ['data1', 1234, null, '', 'data5'];
     *
     * @throws Exception If unable to write data
     */
    protected function addRowToWriter(array $dataRow)
    {
        $wasWriteSuccessful = fputcsv($this->filePointer, $dataRow, $this->delimiter, $this->enclosure);
        if ($wasWriteSuccessful === false) {
            throw new Exception('Unable to write data');
        }

        ++$this->lastWrittenRowIndex;
        if ($this->lastWrittenRowIndex % static::FLUSH_THRESHOLD === 0) {
            fflush($this->filePointer);
        }
    }

    /**
     * Closes the CSV streamer, preventing any additional writing.
     * If set, sets the headers and redirects output to the browser.
     */
    protected function closeWriter()
    {
        $this->lastWrittenRowIndex = 0;
    }

    /**
     * Closes the writer. This will close the streamer as well, preventing new data
     * to be written to the file.
     */
    public function close()
    {
        $this->closeWriter();

        if (is_resource($this->filePointer)) {
            fclose($this->filePointer);
        }

        $this->isWriterOpened = false;
    }

    /**
     * Closes the writer and attempts to cleanup all files that were
     * created during the writing process (temp files & final file).
     */
    private function closeAndAttemptToCleanupAllFiles()
    {
        // close the writer, which should remove all temp files
        $this->close();

        if (file_exists($this->outputFilePath) && is_file($this->outputFilePath)) {
            unlink($this->outputFilePath);
        }
    }

    /**
     * 根据列数生成Excel的坐标(A1, B1... AA1, AB1)
     * Excel限制
     * 在 Excel 2010 和 Excel 2007 中，工作表的大小为 16,384 列 X 1,048,576 行，
     * 而在 Excel 97-2003 中，工作表的大小仅为 256 列 X 65,536 行。
     *
     * @param int    $colums
     * @param string $row
     * @param bool   $newVersion 版本大于等于2007
     *
     * @return array
     */
    public static function makeExcelCoordinates(int $colums = 1, string $row = '', bool $newVersion = true): array
    {
        if ($newVersion) {
            if ($colums > 16384 || $row > 1048576) {
                return [];
            }
        } else {
            if ($colums > 256 || $row > 65536) {
                return [];
            }
        }

        $coordinates = [];
        for ($i = 0; $i < $colums; ++$i) {
            $coordinates[] = self::makeExcelCoordinate($i, $row);
        }

        return $coordinates;
    }

    /**
     * 生成第$rows列的坐标
     *
     * @param int    $column
     * @param string $row
     *
     * @return string
     */
    private static function makeExcelCoordinate(int $column = 1, string $row = '') :string
    {
        $first = 65;

        $step = intval($column / 26);
        $mod  = $column % 26;

        $cell   = chr($first + $mod);
        $prefix = $step == 0 ? '' : chr($first + $step - 1);

        return $prefix . $cell . $row;
    }

    /**
     * 保存内容为excel
     *
     * $mergeCell为合并单元格，
     * 数据结构：
     *  [
     *      [
     *          [1, 0], [2, 0]
     *      ],
     *      [
     *          [1, 1], [2, 1]
     *      ]
     *  ]
     * 其中数字为导出数据在数组中的下标
     * 如上表示 从元素 1,0 合并到元素 2,0，对应单元格为：A1:B1
     * 如果有多个tab（即 $multiple 为true），则该字段外面再加一层数组
     *$lockArr 格式同$mergeCell
     *
     * @param array     $dataList
     * @param string    $filename
     * @param bool      $csv
     * @param bool      $multiple
     * @param array     $mergeCell
     * @param array     $lockCell
     */
    public static function writeToExcel(
        array $dataList,
        string $filename,
        bool $csv = false,
        bool $multiple = false,
        array $mergeCell = [],
        array $lockCell = []
    ) {
        if (!$multiple) {
            $dataList  = [$dataList];
            $mergeCell = [$mergeCell];
            $lockCell  = [$lockCell];
        }

        try {
            $excel = new Spreadsheet();

            $i = 1;
            while ($i++ < count($dataList)) {
                $excel->createSheet();
            }

            $index = 0;
            foreach ($dataList as $title => $data) {
                $excel->setActiveSheetIndex($index);
                $activeSheet = $excel->getActiveSheet();

                if (!is_numeric($title)) {
                    $activeSheet->setTitle($title);
                }

                $rows           = 0;
                $coordinatesArr = [];

                foreach ($data as $line) {
                    $coordinatesArr[] = $coordinates = self::makeExcelCoordinates(count($line), ++$rows);

                    for ($i = 0; $i < count($line); ++$i) {
                        // 过长的数字以字符串显示，以免科学计数法
                        if (is_numeric($line[$i])) {
                            $activeSheet->setCellValueExplicit($coordinates[$i], $line[$i],
                                strlen($line[$i]) >= 10 ? DataType::TYPE_STRING : DataType::TYPE_NUMERIC);
                        } elseif (is_string($line[$i])) {
                            $activeSheet->setCellValueExplicit($coordinates[$i], $line[$i], DataType::TYPE_STRING);
                        } else {
                            $activeSheet->setCellValue($coordinates[$i], $line[$i]);
                        }
                    }
                }

                if (!$csv) {
                    $activeSheet->getStyleByColumnAndRow(0, 1)
                                ->getFont()
                                ->setSize(12);
                }

                // 合并单元格
                if (isset($mergeCell[$index])) {
                    foreach ($mergeCell[$index] as $mergeCellItem) {
                        // $pRange = A1:A3 表示从A1合并到A3
                        $pRange = $coordinatesArr[$mergeCellItem[0][0]][$mergeCellItem[0][1]] .
                                  ':' . $coordinatesArr[$mergeCellItem[1][0]][$mergeCellItem[1][1]];

                        $activeSheet->mergeCells($pRange);
                    }
                }

                //设置可编辑范围
                if ($lockCell[$index]) {
                    $activeSheet->getProtection()
                                ->setSheet(true);
                    foreach ($lockCell[$index] as $lockCellRange) {
                        $pRange = $coordinatesArr[$lockCellRange[0][0]][$lockCellRange[0][1]]
                                  . ':' . $coordinatesArr[$lockCellRange[1][0]][$lockCellRange[1][1]];

                        $activeSheet->getStyle($pRange)
                                    ->getProtection()
                                    ->setLocked(Protection::PROTECTION_UNPROTECTED);
                    }
                }
                ++$index;
            }

            if ($csv) {
                $writer = IOFactory::createWriter($excel, 'Csv');
                $writer->setUseBOM(true);
            } else {
                $writer = IOFactory::createWriter($excel, 'Xlsx');
            }

            $writer->save($filename);

        } catch (Exception $ex) {
            Log::error("write_excel_error", ['message' => $ex->getMessage()]);
        }
    }

    /**
     * 根据索引生成对应的列
     *
     * @param int $pColumnIndex
     *
     * @return string
     */
    public static function stringFromColumnIndex(int $pColumnIndex = 0): string
    {
        return Coordinate::stringFromColumnIndex($pColumnIndex);
    }


    /**
     * 读取excel内容返回，组装好的数据和总条数
     *
     * @param array  $title        标题
     * @param string $import_excel 表单字段name
     *
     * @return array
     */
    public static function readExcel(array $files,array $title): array
    {
        try {
            //验证文件是否存在
            if (!file_exists($files['tmp_file']) ||
                !is_file($files['tmp_file']) ||
                !is_readable($files['tmp_file'])) {
                throw new Exception('文件不存在');
            }

            //excel取出的数据
            $excelData = [];
            // 导入第一列为序号，所以要占位
            array_unshift($title, '');
            $count = count($title);

            $excel = IOFactory::load($files['tmp_file']);
            //选择标签页
            $sheet = $excel->getSheet(0);
            //获取总行数
            $totalRow = $sheet->getHighestRow();

            for ($i = 2; $i <= $totalRow; ++$i) {
                $row = [];
                // 行状态， 默认为false无效,  如果title对应的列有值，则认为此行有效
                $row_status = false;
                for ($j = 1; $j < $count; ++$j) {
                    $cellName = OfficeHelper::stringFromColumnIndex($j) . $i;
                    $cellVal  = trim($sheet->getCell($cellName)
                                           ->getValue());
                    if ($cellVal) {
                        $row_status = true;
                    }

                    $thisTitle       = $title[$j];
                    $row[$thisTitle] = $cellVal;
                }

                // 如果此行title对应的所有列数据为空，则中断循换：excel 粘贴容易出现空白行
                if ($row_status === false) {
                    $totalRow = --$i;
                    break;
                }

                $excelData[$i] = $row;
            }

        } catch (Exception $ex) {

            return [];
        }

        return [$excelData, $totalRow];
    }
}
