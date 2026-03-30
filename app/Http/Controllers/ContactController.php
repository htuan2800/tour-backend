<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    protected $contactService;

    public function __construct(ContactService $contactService)
    {
        $this->contactService = $contactService;
    }

    public function send(ContactRequest $request): JsonResponse
    {
        $data = $request->validated();
        $isSent = $this->contactService->sendContactEmail($data);
        if ($isSent) {
            return $this->success('Gửi liên hệ thành công. Chúng tôi sẽ phản hồi sớm nhất có thể!');
        }
        return $this->error('Có lỗi xảy ra trong quá trình gửi mail. Vui lòng thử lại sau.', 500);
    }
}