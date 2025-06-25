<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class IndustrySeeder extends Seeder
{
    public function run(): void
    {
        $industries = [
            ['name' => json_encode(['cn' => '信息技术', 'en' => 'Information Technology'])],
            ['name' => json_encode(['cn' => '金融服务', 'en' => 'Financial Services'])],
            ['name' => json_encode(['cn' => '保险', 'en' => 'Insurance'])],
            ['name' => json_encode(['cn' => '房地产', 'en' => 'Real Estate'])],
            ['name' => json_encode(['cn' => '制造业', 'en' => 'Manufacturing'])],
            ['name' => json_encode(['cn' => '建筑业', 'en' => 'Construction'])],
            ['name' => json_encode(['cn' => '教育', 'en' => 'Education'])],
            ['name' => json_encode(['cn' => '医疗保健', 'en' => 'Healthcare'])],
            ['name' => json_encode(['cn' => '制药业', 'en' => 'Pharmaceuticals'])],
            ['name' => json_encode(['cn' => '零售', 'en' => 'Retail'])],
            ['name' => json_encode(['cn' => '批发', 'en' => 'Wholesale'])],
            ['name' => json_encode(['cn' => '运输与物流', 'en' => 'Transportation & Logistics'])],
            ['name' => json_encode(['cn' => '旅游与酒店', 'en' => 'Travel & Hospitality'])],
            ['name' => json_encode(['cn' => '法律服务', 'en' => 'Legal Services'])],
            ['name' => json_encode(['cn' => '市场营销与广告', 'en' => 'Marketing & Advertising'])],
            ['name' => json_encode(['cn' => '媒体与娱乐', 'en' => 'Media & Entertainment'])],
            ['name' => json_encode(['cn' => '电信', 'en' => 'Telecommunications'])],
            ['name' => json_encode(['cn' => '农业', 'en' => 'Agriculture'])],
            ['name' => json_encode(['cn' => '食品与饮料', 'en' => 'Food & Beverage'])],
            ['name' => json_encode(['cn' => '能源', 'en' => 'Energy'])],
            ['name' => json_encode(['cn' => '石油与天然气', 'en' => 'Oil & Gas'])],
            ['name' => json_encode(['cn' => '公共事业', 'en' => 'Utilities'])],
            ['name' => json_encode(['cn' => '公共管理', 'en' => 'Public Administration'])],
            ['name' => json_encode(['cn' => '非营利组织', 'en' => 'Non-Profit Organization'])],
            ['name' => json_encode(['cn' => '航空航天', 'en' => 'Aerospace'])],
            ['name' => json_encode(['cn' => '汽车业', 'en' => 'Automotive'])],
            ['name' => json_encode(['cn' => '艺术与设计', 'en' => 'Arts & Design'])],
            ['name' => json_encode(['cn' => '化学工业', 'en' => 'Chemical Industry'])],
            ['name' => json_encode(['cn' => '环境服务', 'en' => 'Environmental Services'])],
            ['name' => json_encode(['cn' => '其他', 'en' => 'Others'])],
        ];

        DB::table('industries')->insert($industries);
    }
}
