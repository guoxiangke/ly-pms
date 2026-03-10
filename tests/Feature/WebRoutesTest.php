<?php

namespace Tests\Feature;

use App\Models\LyMeta;
use App\Models\LyItem;
use App\Models\LtsMeta;
use App\Models\LtsItem;
use App\Models\User;
use Tests\TestCase;

class WebRoutesTest extends TestCase
{
    /**
     * 首页：本地环境返回 welcome 视图
     */
    public function test_home_page_returns_ok_or_redirect(): void
    {
        $response = $this->get('/');

        $response->assertStatus(app()->isLocal() ? 200 : 302);
    }

    /**
     * /login 应重定向到 Nova 首页
     */
    public function test_login_redirects_to_nova(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(302);
    }

    /**
     * /dashboard 未登录应重定向
     */
    public function test_dashboard_requires_auth(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect();
    }

    /**
     * /dashboard 登录后返回 200
     */
    public function test_dashboard_accessible_when_authenticated(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    /**
     * /file/submission 未登录应重定向
     */
    public function test_upload_requires_auth(): void
    {
        $response = $this->get('/file/submission');

        $response->assertRedirect();
    }

    /**
     * /file/submission 登录后返回 200
     */
    public function test_upload_accessible_when_authenticated(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($user)->get('/file/submission');

        $response->assertStatus(200);
    }

    /**
     * /admin/pulse 未登录应重定向
     */
    public function test_pulse_requires_auth(): void
    {
        $response = $this->get('/admin/pulse');

        $response->assertRedirect();
    }

    /**
     * /admin/pulse 登录后返回 200
     */
    public function test_pulse_accessible_when_authenticated(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($user)->get('/admin/pulse');

        $response->assertStatus(200);
    }

    /**
     * /ly/corrections/{version_folder}/{mp3} 应重定向到 CloudFront
     */
    public function test_corrections_redirect_to_cloudfront(): void
    {
        $response = $this->get('/ly/corrections/v1/test.mp3');

        $response->assertStatus(302);
        $response->assertRedirectContains('/ly/corrections/v1/test.mp3');
    }

    /**
     * /storage/ly/corrections/{version_folder}/{mp3} 应重定向到 CloudFront
     */
    public function test_storage_corrections_redirect_to_cloudfront(): void
    {
        $response = $this->get('/storage/ly/corrections/v1/test.mp3');

        $response->assertStatus(302);
        $response->assertRedirectContains('/ly/corrections/v1/test.mp3');
    }

    /**
     * /storage/ly/audio/{year}/{code}/{day}.mp3 应重定向到 CloudFront
     */
    public function test_ly_audio_redirect_to_cloudfront(): void
    {
        $year = date('y');
        $code = 'aw';
        $day = $code . date('ymd', strtotime('-3 days'));

        $response = $this->get("/storage/ly/audio/{$year}/{$code}/{$day}.mp3");

        $response->assertStatus(302);
        $response->assertRedirectContains('.mp3');
    }

    /**
     * /storage/ly/audio/{code}/{day}.mp3 (LTS audio) 应重定向到 CloudFront
     */
    public function test_lts_audio_redirect_to_cloudfront(): void
    {
        $response = $this->get('/storage/ly/audio/testcode/test001.mp3');

        $response->assertStatus(302);
        $response->assertRedirectContains('.mp3');
    }

    /**
     * /program/{code} 使用有效的 LyMeta code 应返回 200
     */
    public function test_program_page_with_valid_ly_meta(): void
    {
        $lyMeta = LyMeta::first();

        if (!$lyMeta) {
            $this->markTestSkipped('没有 LyMeta 数据，跳过测试');
        }

        $response = $this->get("/program/{$lyMeta->code}");

        $this->assertContains($response->getStatusCode(), [200, 403]);
    }

    /**
     * /program/{code} 路由能正确匹配（LtsMeta 分支）
     */
    public function test_program_page_with_valid_lts_meta(): void
    {
        $ltsMeta = LtsMeta::whereNotIn('code', LyMeta::pluck('code'))->first();

        if (!$ltsMeta) {
            $this->markTestSkipped('没有独立 LtsMeta 数据，跳过测试');
        }

        $response = $this->get("/program/{$ltsMeta->code}");

        $this->assertContains($response->getStatusCode(), [200, 403]);
    }

    /**
     * /program/{code} 使用无效 code 应返回 404
     */
    public function test_program_page_with_invalid_code(): void
    {
        $response = $this->get('/program/nonexistent_code_xyz');

        $response->assertStatus(404);
    }

    /**
     * /program/mavhx0 软删除的记录应返回 404
     */
    public function test_program_page_mavhx0_soft_deleted_returns_404(): void
    {
        $response = $this->get('/program/mavhx0');

        $response->assertStatus(404);
    }

    /**
     * /program/{code} 软删除的记录应返回 404（通用）
     */
    public function test_program_page_with_soft_deleted_code_returns_404(): void
    {
        $softDeleted = LtsMeta::onlyTrashed()->first();

        if (!$softDeleted) {
            $this->markTestSkipped('没有软删除的 LtsMeta 数据，跳过测试');
        }

        // 确保该 code 不在 LyMeta 中（否则会走 LyMeta 分支）
        if (LyMeta::where('code', $softDeleted->code)->exists()) {
            $this->markTestSkipped('该软删除的 code 在 LyMeta 中也存在，跳过测试');
        }

        $response = $this->get("/program/{$softDeleted->code}");

        $response->assertStatus(404);
    }

    /**
     * LTS 节目通过 LyMeta (isLts=true, code 以 lts 开头)
     * 测试: ltsnp, ltstpa1, ltstpa2, ltstpb1, ltstpb2
     */
    public function test_program_page_lts_via_ly_meta(): void
    {
        $codes = ['ltsnp', 'ltstpa1', 'ltstpa2', 'ltstpb1', 'ltstpb2'];

        foreach ($codes as $code) {
            $lyMeta = LyMeta::where('code', $code)->first();

            if (!$lyMeta) {
                continue;
            }

            $response = $this->get("/program/{$code}");

            $this->assertContains(
                $response->getStatusCode(),
                [200, 403],
                "路由 /program/{$code} 返回了意外状态码: {$response->getStatusCode()}"
            );
        }
    }

    /**
     * LTS 节目通过 LtsMeta (code 以 mav 开头，仅存在于 lts_metas)
     * 测试: mavhx1, mavspmc6
     */
    public function test_program_page_lts_via_lts_meta(): void
    {
        $codes = ['mavhx1', 'mavspmc6'];

        foreach ($codes as $code) {
            $ltsMeta = LtsMeta::where('code', $code)->first();

            if (!$ltsMeta) {
                continue;
            }

            $response = $this->get("/program/{$code}");

            $this->assertContains(
                $response->getStatusCode(),
                [200, 403],
                "路由 /program/{$code} 返回了意外状态码: {$response->getStatusCode()}"
            );
        }
    }

    /**
     * 每个有效的 /program/{code} 播放列表必须包含至少一条有 path 的音频
     */
    public function test_program_playlist_has_music_with_path(): void
    {
        $codes = ['ltsnp', 'ltstpa1', 'ltstpa2', 'ltstpb1', 'ltstpb2', 'mavhx1', 'mavspmc6'];

        foreach ($codes as $code) {
            $lyMeta = LyMeta::where('code', $code)->first();

            if ($lyMeta) {
                if ($lyMeta->isLts) {
                    $playlist = $lyMeta->ltsItems()->get();
                } else {
                    $playlist = $lyMeta->ly_items()->get();
                }
            } else {
                $ltsMeta = LtsMeta::where('code', $code)->first();
                if (!$ltsMeta) {
                    continue;
                }
                $playlist = $ltsMeta->lts_items()->get();
            }

            $music = $playlist->first();
            $this->assertNotNull($music, "播放列表 /program/{$code} 没有任何音频");
            $this->assertNotEmpty($music->path, "播放列表 /program/{$code} 第一条音频缺少 path");
        }
    }

    /**
     * /program/{code}?order=1 排序参数应正常工作
     */
    public function test_program_page_with_order_param(): void
    {
        $lyMeta = LyMeta::first();

        if (!$lyMeta) {
            $this->markTestSkipped('没有 LyMeta 数据，跳过测试');
        }

        $response = $this->get("/program/{$lyMeta->code}?order=1");

        $this->assertContains($response->getStatusCode(), [200, 403]);
    }

    /**
     * /share/{hashId} 使用有效的 LyItem hashId
     */
    public function test_share_page_with_valid_ly_item(): void
    {
        $lyItem = LyItem::first();

        if (!$lyItem) {
            $this->markTestSkipped('没有 LyItem 数据，跳过测试');
        }

        $hashId = $lyItem->hashId;

        $response = $this->get("/share/{$hashId}");

        $response->assertStatus(200);
    }

    /**
     * /share/{hashId} 使用有效的 LtsItem hashId
     */
    public function test_share_page_with_valid_lts_item(): void
    {
        $ltsItem = LtsItem::whereHas('ltsMeta', fn ($q) => $q->whereNotNull('ly_meta_id'))->first();

        if (!$ltsItem) {
            $this->markTestSkipped('没有有 ly_meta 关联的 LtsItem 数据，跳过测试');
        }

        $hashId = $ltsItem->hashId;

        $response = $this->get("/share/{$hashId}");

        $response->assertStatus(200);
    }

    /**
     * /share/{hashId} 使用无效 hashId 应返回错误
     */
    public function test_share_page_with_invalid_hash_id(): void
    {
        $response = $this->get('/share/invalid_hash_id');

        $this->assertContains($response->getStatusCode(), [404, 500]);
    }

    /**
     * /share/lyi_v8O3z5JpZVDXK 旷野吗哪分享页
     * 验证：有文本内容、有音乐链接、title 和 desc 正确
     */
    public function test_share_page_lyi_v8O3z5JpZVDXK(): void
    {
        $hashId = 'lyi_v8O3z5JpZVDXK';
        $lyItem = LyItem::with(['ly_meta', 'contents.attachments'])->findOrFail(LyItem::keyFromHashId($hashId));
        $lyMeta = $lyItem->ly_meta;

        // 验证数据完整性
        $this->assertNotEmpty($lyItem->path, '音频链接 path 不能为空');
        $this->assertNotEmpty($lyItem->description, 'description 不能为空');
        $this->assertTrue($lyItem->contents->count() > 0, '必须有文本内容');

        // 验证 title: {{$lyItem->description}}
        $expectedTitle = $lyItem->description;
        $this->assertEquals("\u{201c}敬虔\u{201d}是最重要的事（哥林多前书9:24-10:12）", $expectedTitle);

        // 验证 desc: {{$lyMeta->name}}-{{$lyItem->play_at->format('Ymd')}}
        $expectedDesc = $lyMeta->name . '-' . $lyItem->play_at->format('Ymd');
        $this->assertEquals('旷野吗哪-20260310', $expectedDesc);

        // 验证页面渲染
        $response = $this->get("/share/{$hashId}");
        $response->assertStatus(200);

        // 页面包含音频链接
        $response->assertSee($lyItem->path, false);

        // 页面包含 title 和 desc
        $response->assertSee($expectedTitle, false);
        $response->assertSee($expectedDesc, false);

        // 页面包含文本内容（body 是 markdown，页面渲染为 HTML，用金句片段验证）
        $response->assertSee('岂不知在场上赛跑的都跑', false);
    }
}
