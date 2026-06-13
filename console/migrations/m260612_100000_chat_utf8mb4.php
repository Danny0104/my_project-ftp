<?php

use yii\db\Migration;

/**
 * Ensure chat message text supports emoji (utf8mb4).
 */
class m260612_100000_chat_utf8mb4 extends Migration
{
    public function safeUp()
    {
        $this->execute('ALTER TABLE {{%chat_message}} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->execute('ALTER TABLE {{%chat_conversation}} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    }

    public function safeDown()
    {
        $this->execute('ALTER TABLE {{%chat_message}} CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $this->execute('ALTER TABLE {{%chat_conversation}} CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
    }
}
