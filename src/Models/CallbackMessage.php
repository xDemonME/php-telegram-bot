<?php

namespace PhpTelegramBot\Laravel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Longman\TelegramBot\Entities\ServerResponse;

class CallbackMessage extends Model
{
    use HasFactory;

    /**
     * Таблица БД, ассоциированная с моделью.
     *
     * @var string
     */
    protected $table = 'bot_callback_messages';
    protected $guarded = [];

    public static function get(int|string $chat_id, ?array $callback_data = null) {
        if(!empty($callback_data['uuid'])) {
            $botMessage = self::where('timestamp', $callback_data['uuid'])->where('chat_id', $chat_id)->first();
        } else {
            $botMessage = new self([
                'timestamp' => time(),
                'chat_id' => $chat_id,
            ]);
        }
        return $botMessage;
    }

    public function set(ServerResponse $result) {
        $this->message_id = $result->getResult()?->message_id;
        if ($this->message_id) {
            $this->save();
        }
    }

    public function getUuidAttribute() {
        return $this->timestamp;
    }
}
