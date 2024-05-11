<?php

namespace PhpTelegramBot\Laravel;

use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Entities\Chat;
use Longman\TelegramBot\Entities\Message;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Entities\User;
use Longman\TelegramBot\Request;

class LaravelTelegramBot
{

    private Message $message;
    protected Chat $chat;
    protected User $user;
    protected int|string $chatId;
    protected int $userId;
    protected ?CallbackQuery $callbackQuery = null;
    protected ?string $text;

    protected array $callbacks = [];

    public function register(callable $callback)
    {
        $this->callbacks[] = $callback;
    }

    public function call(Update $update): ?ServerResponse
    {
        foreach ($this->callbacks as $callback) {
            $return = $callback($update);

            if ($return instanceof ServerResponse) {
                return $return;
            } elseif ($return === true) {
                return Request::emptyResponse();
            }
        }

        return null;
    }

    public function setMessage(Message $message): static
    {
        $this->message = $message;
        $this->setChat($this->message->getChat());
        $this->setUser($this->message->getFrom());
        return $this;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function setChat(Chat $chat): static
    {
        $this->chat = $chat;
        $this->chatId = $this->chat->getId();
        return $this;
    }

    public function getChat(): Chat
    {
        return $this->chat;
    }

    public function setChatId(int|string $chatId): static
    {
        $this->chatId = $chatId;
        return $this;
    }

    public function getChatId(): int|string
    {
        return $this->chatId;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        $this->userId = $this->user->getId();
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUserId(int|string $userId): static
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUserId(): int|string
    {
        return $this->userId;
    }

    public function setText(?string $text): static
    {
        $this->text = trim($text);
        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setCallbackQuery(?CallbackQuery $callbackQuery): static
    {
        $this->callbackQuery = $callbackQuery;
        return $this;
    }

    public function getCallbackQuery(): ?CallbackQuery
    {
        return $this->callbackQuery;
    }
}
