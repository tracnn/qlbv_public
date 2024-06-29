<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\CustomUser;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    public function login(Request $request)
    {
        $this->validateLogin($request);

        // Lấy thông tin đăng nhập từ request
        $username = strtolower($request->get('email'));
        $password = $request->get('password');
        $secureText = '!@#$%^&*())(*&^%$#@!'; // Nếu bạn có thêm salt hoặc chuỗi bảo mật
        $password = $password . $secureText; // Kết hợp mật khẩu với chuỗi bảo mật (nếu có)
        $hashedPassword = hash('sha512', $password);

        // Tìm kiếm người dùng dựa trên username (hoặc email)
        $user = CustomUser::whereRaw("LOWER(loginname) = ?", [$username])
        ->where('is_active', 1)
        ->first();

        // Kiểm tra nếu người dùng tồn tại và mật khẩu hash khớp
        if ($user && hash_equals($user->password, $hashedPassword)) {
            // Đăng nhập thành công, thiết lập phiên cho người dùng
            Auth::login($user);

            // Chuyển hướng người dùng đến trang dự định
            return redirect()->intended($this->redirectPath());
        }

        // Nếu xác thực thất bại, trả về lỗi
        return $this->sendFailedLoginResponse($request);
    }

    protected function validateLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);
    }

   

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        \Cookie::queue('remember',\Request::get('remember'));
        $this->middleware('guest')->except('logout');
    }
}
