rename table user_filter_log to log_filter;

ALTER TABLE log_filter COMMENT = 'Таблица для сбора статистики, какие фильтры используютс, а какие нет';