<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Test khung mac dinh cua Laravel, sua lai cho dung voi ung dung nay.
 *
 * Ban goc khang dinh GET '/' tra 200. No do vi ung dung KHONG co trang cong khai o '/':
 * routes/web.php dang ky '/' HAI LAN - dong 14 tro WelcomeController@index, roi dong 203
 * tro HomeController@index trong nhom middleware auth. Dang ky sau ghi de dang ky truoc, nen
 * '/' thuc te la HomeController@index sau 'web, auth' -> khach chua dang nhap bi day sang
 * /login. Da doi chieu bang router()->getRoutes(): action = HomeController@index,
 * middleware = web, auth.
 *
 * Nen khang dinh dung la: khach VAO DUOC ung dung nhung bi dan sang trang dang nhap. Viet
 * lai nhu vay thi test giu duoc gia tri canh gac - no se do neu ai do go mat 'auth' khoi
 * nhom bao quanh, tuc la mo toang trang chu cho nguoi la.
 */
class ExampleTest extends TestCase
{
    /** @test */
    public function khach_chua_dang_nhap_bi_day_sang_trang_dang_nhap()
    {
        $response = $this->get('/');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
}
