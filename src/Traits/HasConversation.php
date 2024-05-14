<?php

namespace PhpTelegramBot\Laravel\Traits;

use Illuminate\Support\Facades\Log;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use PhpTelegramBot\Laravel\Facades\Telegram;
use PhpTelegramBot\Laravel\Services\Message;

trait HasConversation
{
    protected ?array $notes;
    protected int $state;
    private $steps;

    protected ?string $text;

    protected ?Conversation $conversation;

    protected function startConversation(): ServerResponse
    {
        if (Telegram::getChat()->isGroupChat() || Telegram::getChat()->isSuperGroup()) {
            $this->setKeyboard(Keyboard::forceReply(['selective' => true])); //TODO
        }
        // TODO реализовать прерывание Conversation другими командами

        $userId = Telegram::getUserId();
        $chatId = Telegram::getChatId();

        // Conversation start
        $this->conversation = new Conversation(
            $this->getCallbackQuery() !== null ? $chatId : $userId,
            $chatId,
            $this->getName()
        );

        $this->text = Telegram::getText();
        // Load any existing notes from this conversation
        $this->notes = &$this->conversation->notes;
        !is_array($this->notes) && $this->notes = [];

        // Load the current state of the conversation
        $this->state = $this->notes['state'] ?? 0;
        Log::debug("Conversation debug:\nUser id: $userId\nChat ID: $chatId\nName: {$this->getName()}\n", $this->notes);
        return Request::emptyResponse();
    }

    protected function stopConversation(): ?Conversation
    {
        $this->conversation = new Conversation(
            $this->getMessage()->getFrom()->getId(),
            $this->getMessage()->getChat()->getId()
        );

        $this->conversation->cancel();

        return $this->conversation;
    }

    /**
     * @param callable|array|Message|ServerResponse $callback
     * @return $this
     */
    public function addConversationStep(callable|array|Message|ServerResponse $callback): static
    {
        $this->steps[] = $callback;
        return $this;
    }

    protected function handleConversation(): ServerResponse
    {
        if ($this->text === 'Отменить') {
            $this->conversation->cancel();
            // TODO добавить метод для работы с отменой
            $message = new Message;
            return $message
                ->setText('Добавление новой записи было отменено.')
                ->setKeyboard(new InlineKeyboard([$message->callbackButton('Вернуться в панель управления', 'adminpanel')]))
                ->send();
        }

        for ($i = $this->state; $i < count($this->steps); $i++) {
            $respond = null;
            $isValid = false;
            $step = is_callable($this->steps[$i]) ? $this->steps[$i]() : $this->steps[$i];

            if (is_array($step)) {
                if (count($step) === 2) {
                    [$respond, $isValid] = $step;
                } elseif (count($step) === 1) {
                    [$respond] = $step;
                }
            } else {
                $respond = $step;
            }

            if ($this->text === '' || !$isValid) {
                $this->notes['state'] = $i;
                $this->conversation->update();

                if($respond instanceof Message) {
                    return $respond->send();
                } elseif ($respond instanceof ServerResponse) {
                    return $respond;
                }
            }
            $this->text = '';
        }

        return Request::emptyResponse();
    }


}
