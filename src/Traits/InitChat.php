<?php

namespace PhpTelegramBot\Laravel\Traits;

use Longman\TelegramBot\Entities\ServerResponse;
use PhpTelegramBot\Laravel\Facades\Telegram;
use PhpTelegramBot\Laravel\Models\CallbackMessage;

trait InitChat
{
    protected array $callbackData = [];
    protected CallbackMessage $botMessage;

    public function preExecute(): ServerResponse
    {
        $this->init();
        return parent::preExecute();
    }

    protected function init(): void
    {
        $callbackQuery = $this->getCallbackQuery() ?? null;
        parse_str($callbackQuery?->getData(), $this->callbackData);

        $message = $this->getMessage() ?? $callbackQuery?->getMessage() ?? null;
        $text = $this->getMessage()?->getText(true);

        Telegram::setMessage($message);
        Telegram::setCallbackQuery($callbackQuery);
        Telegram::setText($text);
    }
}
