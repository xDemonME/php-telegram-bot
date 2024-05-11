<?php

namespace PhpTelegramBot\Laravel\Services;

use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use PhpTelegramBot\Laravel\Models\CallbackMessage;

class Message
{
    /**
     * Идентификатор сообщения в чате.
     */
    protected ?int $messageId = null;

    /**
     * Идентификатор чата.
     */
    protected ?int $chatId = null;

    /**
     * Формат разметки текста сообщения.
     */
    protected ?string $parseMode = 'markdown';

    /**
     * Текст сообщения.
     */
    protected ?string $text = null;

    /**
     * Флаг для отключения предпросмотра страниц в сообщениях.
     */
    protected bool $disablePagePreview = false;

    /**
     * Заголовок сообщения.
     */
    private ?string $title = null;

    /**
     * Клавиатура для ответа.
     */
    protected null|InlineKeyboard|Keyboard $replyMarkup = null;

    /**
     * Кнопка для возврата.
     */
    private array $returnButton = [];

    /**
     * Объект сообщения для callback.
     */
    protected ?CallbackMessage $callbackMessage = null;

    /**
     * Запрос callback.
     */
    protected $callbackQuery = null;

    /**
     * Данные запроса callback.
     */
    protected $callbackData = null;

    /**
     * Устанавливает запрос callback и обрабатывает его данные.
     */
    public function setCallbackQuery(?CallbackQuery $callbackQuery = null): static
    {
        $this->callbackQuery = $callbackQuery;
        parse_str($this->callbackQuery?->getData(), $this->callbackData);
        $this->callbackMessage = CallbackMessage::get($this->chatId, $this->callbackData);

        if ($this->callbackMessage->exists) {
            $this->messageId = $this->callbackMessage->message_id;
        }

        return $this;
    }

    /**
     * Устанавливает идентификатор сообщения.
     */
    public function setMessageId($messageId): static
    {
        $this->messageId = $messageId;

        return $this;
    }

    public function getMessageId(): ?int
    {
        return $this->messageId;
    }

    /**
     * Отмечает сообщение как новое, сбрасывая его идентификатор.
     */
    public function asNewMessage(): static
    {
        $this->messageId = null;

        return $this;
    }

    /**
     * Устанавливает идентификатор чата.
     */
    public function setChatId($chatId): static
    {
        $this->chatId = $chatId;

        return $this;
    }

    /**
     * Устанавливает текст сообщения.
     */
    public function setText($text): static
    {
        $this->text = $text;
        return $this;
    }

    /**
     * Устанавливает заголовок сообщения.
     */
    public function setTitle($title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Устанавливает формат разметки текста.
     */
    public function setParseMode($parseMode = 'markdown'): static
    {
        $this->parseMode = $parseMode;

        return $this;
    }

    /**
     * Отключает предпросмотр страниц в сообщениях.
     */
    public function disablePagePreview($state = true): static
    {
        $this->disablePagePreview = $state;

        return $this;
    }

    /**
     * Устанавливает клавиатуру для ответа.
     */
    public function setKeyboard(InlineKeyboard|Keyboard $reply_markup): static
    {
        $this->replyMarkup = $reply_markup;
        return $this;
    }

    /**
     * Удаляет клавиатуру ответа.
     */
    public function removeKeyboard(array $data = ['selective' => true]): static
    {
        $this->replyMarkup = Keyboard::remove($data);

        return $this;
    }

    /**
     * Сбрасывает клавиатуру ответа.
     */
    public function resetKeyboard(): static
    {
        $this->replyMarkup = null;

        return $this;
    }

    /**
     * Устанавливает кнопку возврата.
     */
    public function setReturnButton($returnCommand = 'start', $asNewMessage = false, $text = '🔙 Вернуться'): static
    {
        $this->returnButton = $this->callbackButton($text, $returnCommand, [], $asNewMessage);

        return $this;
    }

    public function wait($text = "Идет загрузка... пожалуйста подождите..."): ServerResponse
    {
        $this->setCallbackQuery();
        $message = clone $this;
        $response = $message->setText($text)
            ->removeKeyboard()
            ->send();

        $this->setMessageId($message->getMessageId());

        return $response;
    }

    /**
     * Конвертирует данные сообщения в массив.
     */
    public function toArray(): array
    {
        $data = [
            'chat_id' => $this->chatId,
            'text' => $this->text,
            'parse_mode' => $this->parseMode,
        ];

        if ($this->replyMarkup) {
            $data['reply_markup'] = $this->replyMarkup;
        }

        if ($this->disablePagePreview) {
            $data['disable_web_page_preview'] = $this->disablePagePreview;
        }

        if ($this->messageId) {
            $data['message_id'] = $this->messageId;
        }

        if ($this->title) {
            $data['text'] = "<b>$this->title</b>" . PHP_EOL . PHP_EOL . $data['text'];
        }

        if ($this->returnButton) {
            if (!empty($data['reply_markup']) && $data['reply_markup'] instanceof InlineKeyboard) {
                $data['reply_markup']->addRow($this->returnButton);
            } elseif (empty($data['reply_markup'])) {
                $data['reply_markup'] = new InlineKeyboard([$this->returnButton]);
                // TODO подумать о сценарии, если reply_markup = RemoveKeyboard
            }
        }

        return $data;
    }

    /**
     * Отправляет сообщение или редактирует существующее.
     */
    public function send($save = true): ServerResponse
    {
        $data = $this->toArray();
        if(
            !empty($this->messageId) && (empty($this->replyMarkup) || $this->replyMarkup instanceof InlineKeyboard)
        ) {
            $result = Request::editMessageText($data);
        } else {
            $result = Request::sendMessage($data);
            $this->messageId = $result->getResult()?->message_id;

            if ($save && $this->callbackMessage) {
                $this->callbackMessage->set($result);
            }
        }
        \Log::debug('data', $data);
        \Log::debug("result {$result->toJson()}");
        return $this->callbackQuery ? $this->callbackQuery->answer() : $result;
    }

    /**
     * Генерирует данные для callback кнопки.
     */
    public function callback(string $command, array $data = [], $asNewMessage = false): string
    {
        $data['command'] = $command;
        if(!$asNewMessage && $this->callbackMessage->uuid) {
            $data['uuid'] = (string) $this->callbackMessage->uuid;
        }
        return http_build_query($data);
    }

    /**
     * Создает кнопку с callback данными.
     */
    public function callbackButton($name, string $command, array $data = [], $asNewMessage = false): array
    {
        return ['text' => $name, 'callback_data' => $this->callback($command, $data, $asNewMessage)];
    }
}