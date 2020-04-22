<?php


namespace App\Response;


use Symfony\Component\HttpFoundation\JsonResponse;

class AjaxResponse extends JsonResponse
{
    const AJAX_CONTROL_PROCESS = 0;
    const AJAX_CONTROL_RESET = 1;
    const AJAX_CONTROL_CANCEL = 2;

    private $control = self::AJAX_CONTROL_PROCESS;

    public function setAjaxControl( int $control ) {
        $this->control = $control;
        return $this->update();
    }

    public static function errorMessage(string $message): AjaxResponse {
        return static::error('message', ['message' => $message]);
    }

    public static function error(?string $code = null, ?array $additional = []): AjaxResponse {
        if (!is_array($additional)) $additional = [];
        $additional['error'] = $code ?? 'unknown_error';
        return new AjaxResponse($additional);
    }

    public static function success($data = true, ?array $additional = []): AjaxResponse {
        if (!is_array($additional)) $additional = [];
        $additional['success'] = $data;
        return new AjaxResponse($additional);
    }

    protected function update()
    {
        $r = parent::update();
        switch ($this->control) {
            case self::AJAX_CONTROL_PROCESS:
                $this->headers->set('X-AJAX-Control', 'process');
                break;
            case self::AJAX_CONTROL_RESET:
                $this->data = null;
                $this->headers->set('X-AJAX-Control', 'reset');
                break;
            case self::AJAX_CONTROL_CANCEL:
                $this->data = null;
                $this->headers->set('X-AJAX-Control', 'cancel');
                break;
        }
        return $r;
    }

}