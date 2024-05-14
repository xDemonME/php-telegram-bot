<?php

namespace PhpTelegramBot\Laravel\Services;

use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\KeyboardButton;
use PhpTelegramBot\Laravel\Facades\Telegram;
use PhpTelegramBot\Laravel\Models\CallbackMessage;

class CallbackButton extends InlineKeyboardButton
{
    protected string $name;
    protected string $command;
    protected array $data;
    protected bool $asNewMessage = false;
    /**
     * Объект сообщения для callback.
     */
    protected ?CallbackMessage $callbackMessage = null;

    public function __construct(string $name, string $command, array $data = [], null|bool|CallbackMessage|Message $message = false)
    {
        $this->name = $name;
        $this->data = $data;
        $this->data['command'] = $command;
        if (is_bool($message)) {
            $this->asNewMessage = $message;
        } elseif ($message instanceof CallbackMessage) {
            $this->callbackMessage = $message;
        } elseif ($message instanceof Message) {
            $this->callbackMessage = $message->getCallbackMessage();
        } else {
            $this->callbackMessage = Telegram::getCallbackMessage();
        }

        parent::__construct($this->prepareData());
    }

    /**
     * Создает кнопку с callback данными.
     */
    public function prepareData(): array
    {
        if(!$this->asNewMessage && $this->callbackMessage?->uuid) {
            $this->data['uuid'] = (string) $this->callbackMessage->uuid;
        }

        return ['text' => $this->name, 'callback_data' => http_build_query($this->data)];
    }

    public function make()
    {
        $this->prepareData();

        parent::__construct($this->prepareData());
    }
}
