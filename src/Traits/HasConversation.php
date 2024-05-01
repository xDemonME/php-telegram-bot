<?php

namespace PhpTelegramBot\Laravel\Traits;

use Illuminate\Support\Facades\Log;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use PhpTelegramBot\Laravel\Services\Telegram\Message;

trait HasConversation
{
    protected ?array $notes;
    protected int $state;
    private $steps;

    protected ?Conversation $conversation;

    protected function startConversation(): ServerResponse
    {
        if ($this->chat->isGroupChat() || $this->chat->isSuperGroup()) {
            $this->setKeyboard(Keyboard::forceReply(['selective' => true]));
        }
        // TODO реализовать прерывание Conversation другими командами


        // Conversation start
        $this->conversation = new Conversation(
            $this->getCallbackQuery() !== null ? $this->chatId : $this->userId,
            $this->chatId,
            $this->getName()
        );

        // Load any existing notes from this conversation
        $this->notes = &$this->conversation->notes;
        !is_array($this->notes) && $this->notes = [];

        // Load the current state of the conversation
        $this->state = $this->notes['state'] ?? 0;
        Log::debug("Conversation debug:\nUser id: $this->userId\nChat ID: $this->chatId\nName: {$this->getName()}\n", $this->notes);
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
     * @param callable|null $responseCallback
     * @param bool|callable $validationCallback
     * @return $this
     */
    public function addConversationStep(null|callable|Message $responseCallback, bool|callable $validationCallback = true): static
    {
        $this->steps[] = [
            'validate' => $validationCallback,
            'respond'  => $responseCallback
        ];
        return $this;
    }

    protected function handleConversation(): ServerResponse
    {

        if ($this->text === 'Отменить') {
            $this->conversation->cancel();
            $message = (new Message)->setChatId($this->chatId)
                ->setCallbackQuery($this->callbackQuery)
                ->setText('Добавление новой записи было отменено.');
            return $message
                ->setKeyboard(new InlineKeyboard([$message->callbackButton('Вернуться в панель управления', 'adminpanel')]))
                ->send();
        }

        for ($i = $this->state; $i < count($this->steps); $i++) {
            $step = $this->steps[$i];
            if (is_callable($step['validate'])) {
                $validation = $step['validate']();
                if ($validation instanceof Message) {
                    return $validation->send();
                }
            } else {
                $validation = $step['validate'];
            }

            $validation = $validation ?? true;
            if ($step['respond'] && ($this->text === '' || !$validation)) {
                $this->notes['state'] = $i;
                $this->conversation->update();

                $respond = is_callable($step['respond']) ? $step['respond']() : $step['respond'];

                if($respond instanceof Message) {
                    return $respond->send();
                }
            }
            $this->text = '';
        }

        return Request::emptyResponse();
    }
}
