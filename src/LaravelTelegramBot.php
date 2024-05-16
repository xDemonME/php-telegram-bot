<?php

namespace PhpTelegramBot\Laravel;

use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Entities\Chat;
use Longman\TelegramBot\Entities\Message;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Entities\User;
use Longman\TelegramBot\Request;
use PhpTelegramBot\Laravel\Models\CallbackMessage;

class LaravelTelegramBot
{
    private ?Message $message = null;
    protected ?Chat $chat = null;
    protected ?User $user = null;
    protected null|int|string $chatId = null;
    protected null|int $userId = null;
    protected ?CallbackQuery $callbackQuery = null;
    protected ?string $text;
    private ?array $callbackData = null;
    private ?CallbackMessage $callbackMessage = null;
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
        parse_str($this->callbackQuery?->getData(), $this->callbackData);
        $this->setCallbackMessage(CallbackMessage::get($this->chatId, $this->callbackData));
        return $this;
    }

    public function getCallbackQuery(): ?CallbackQuery
    {
        return $this->callbackQuery;
    }

    public function setCallbackData(?array $callbackData): static
    {
        $this->callbackData = $callbackData;
        $this->setCallbackMessage(CallbackMessage::get($this->chatId, $this->callbackData));
        return $this;
    }

    public function setCallbackMessage(CallbackMessage $callbackMessage): static
    {
        $this->callbackMessage = $callbackMessage;
        return $this;
    }

    public function getCallbackMessage(): CallbackMessage
    {
        return $this->callbackMessage;
    }
}
