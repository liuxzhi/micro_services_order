<?php
declare(strict_types=1);
namespace App\Traits\WeightedRoundRobin;

/**
 * 平滑加权轮询算法
 * Trait WeightedRoundRobin
 * @package App\Traits\WeightedRoundRobin
 */
trait WeightedRoundRobin
{
    // 当前权重
    private static $currentWeight = [];

    /**
     * 平滑加权轮询算法
     * @return int|string
     */
    private function weight()
    {
        // 获取配置权重（初始权重）
        $initialWeight = config('initial_weight');

        // 初始化
        if (!self::$currentWeight) {
            self::$currentWeight = array_fill_keys(array_keys($initialWeight), 0);
        }

        // 生效权重 = 当前权重+初始权重
        $effectWeight = [];
        foreach (self::$currentWeight as $key => $value) {
            $effectWeight[$key] = self::$currentWeight[$key] + $initialWeight[$key]['weight'];
        }

        // 获取最大权重的key，为选中key
        arsort($effectWeight);
        $maxWeightKey = key(array_slice($effectWeight, 0, 1, true));

        // 选中key降权
        $totalWeight  = array_sum(array_column($initialWeight, 'weight'));
        $effectWeight[$maxWeightKey] -= $totalWeight;
        self::$currentWeight = $effectWeight;

        return $maxWeightKey;
    }
}
