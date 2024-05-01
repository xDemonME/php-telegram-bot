<?php

namespace PhpTelegramBot\Laravel\Traits;

use PhpTelegramBot\Laravel\Models\CallbackMessage;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Entities\Chat;
use Longman\TelegramBot\Entities\Message;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\User;

trait HasAdditionalFunc
{
    protected ?CallbackQuery $callbackQuery;
    protected array $callbackData = [];
    protected ?Message $message;
    protected ?string $text;

    protected Chat $chat;
    protected int $chatId;

    protected User $user;
    protected int $userId;

     protected CallbackMessage $botMessage;

     public function preExecute(): ServerResponse
     {
         $this->init();
         return parent::preExecute();
     }

    protected function init(): void
    {
        $this->callbackQuery = $this->getCallbackQuery() ?? null;
        parse_str($this->callbackQuery?->getData(), $this->callbackData);

        $this->message = $this->getMessage() ?? $this->callbackQuery?->getMessage() ?? null;
        $this->text    = trim($this->getMessage()?->getText(true));

        $this->chat    = $this->message->getChat();
        $this->chatId = $this->chat->getId();

        $this->user    = $this->message->getFrom();
        $this->userId = $this->user->getId();
    }
}
