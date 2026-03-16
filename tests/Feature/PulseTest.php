<?php

namespace Tests\Feature;

use App\Models\LyItem;
use App\Models\LyMeta;
use App\Models\User;
use Tests\TestCase;

class PulseTest extends TestCase
{
    /**
     * 未登录访问 /admin/pulse 应重定向到登录页
     */
    public function test_pulse_requires_authentication(): void
    {
        $response = $this->get('/admin/pulse');

        $response->assertRedirect();
    }

    /**
     * 登录后访问 /admin/pulse 应返回 200
     */
    public function test_pulse_accessible_when_authenticated(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($user)->get('/admin/pulse');

        $response->assertStatus(200);
    }

    /**
     * Pulse 页面应包含日期表头（当天日期）
     */
    public function test_pulse_shows_date_headers(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($user)->get('/admin/pulse');

        $response->assertStatus(200);
        // 表头应包含当天日期
        $response->assertSee(now()->format('md'), false);
    }

    /**
     * Pulse 页面应显示在播节目的 code
     */
    public function test_pulse_shows_active_program_codes(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $lyMetas = LyMeta::playlistActive()->notLts()->orderBy('code')->get();

        if ($lyMetas->isEmpty()) {
            $this->markTestSkipped('没有在播的非 LTS 节目数据，跳过测试');
        }

        $response = $this->actingAs($user)->get('/admin/pulse');

        $response->assertStatus(200);

        // 页面应包含每个在播节目的 code
        foreach ($lyMetas as $lyMeta) {
            $response->assertSee($lyMeta->code, false);
        }
    }

    /**
     * Pulse 页面应展示 21 天的时间跨度
     */
    public function test_pulse_shows_21_day_span(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($user)->get('/admin/pulse');

        $response->assertStatus(200);

        // 验证最后一天（第 21 天范围内的某天）出现在页面中
        $dayOfWeek = now()->dayOfWeek;
        $before = $dayOfWeek != 1 ? $dayOfWeek - 1 : 0;
        $after = 21 - $before;

        // 最后一天的日期应出现在表头
        $lastDay = now()->addDays($after - 1)->format('md');
        $response->assertSee($lastDay, false);
    }

    /**
     * Pulse 页面应使用 pulse 布局
     */
    public function test_pulse_uses_pulse_layout(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($user)->get('/admin/pulse');

        $response->assertStatus(200);
        // 应包含 table 元素（Pulse 核心是一个表格）
        $response->assertSee('<table', false);
        $response->assertSee('</table>', false);
    }

    /**
     * Pulse 页面有 LyItem 数据时应显示绿色云图标（SVG）
     */
    public function test_pulse_shows_cloud_icon_when_lyitem_exists(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $dayOfWeek = now()->dayOfWeek;
        $before = $dayOfWeek != 1 ? $dayOfWeek - 1 : 0;
        $after = 21 - $before;

        // 查找时间范围内是否有 LyItem
        $hasItems = LyItem::whereBetween('play_at', [
            now()->subDays($before)->startOfDay(),
            now()->addDays($after)->startOfDay(),
        ])->exists();

        if (! $hasItems) {
            $this->markTestSkipped('时间范围内没有 LyItem 数据，跳过测试');
        }

        $response = $this->actingAs($user)->get('/admin/pulse');

        $response->assertStatus(200);
        // 有 LyItem 时应显示绿色云图标
        $response->assertSee('#4caf50', false);
    }

    /**
     * Pulse 页面的路由名称应为 pulse
     */
    public function test_pulse_route_name(): void
    {
        $this->assertEquals('/admin/pulse', route('pulse', [], false));
    }
}
