ALTER TABLE `bot_message`
    ADD COLUMN `story`         TEXT        COMMENT 'Story object. Message is a forwarded stor' AFTER `sticker`;