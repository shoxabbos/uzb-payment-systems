-- phpMyAdmin SQL Dump
-- version 4.7.3
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Фев 15 2018 г., 11:37
-- Версия сервера: 5.6.32-78.1-cll-lve
-- Версия PHP: 5.6.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `vabekuz_maqolalar`
--

-- --------------------------------------------------------

--
-- Структура таблицы `payme_uz`
--

CREATE TABLE `payme_uz` (
  `id` int(11) NOT NULL,
  `transaction` varchar(50) DEFAULT NULL,
  `code` varchar(25) DEFAULT NULL,
  `state` varchar(25) DEFAULT NULL,
  `owner_id` varchar(25) DEFAULT NULL,
  `amount` varchar(25) DEFAULT NULL,
  `reason` varchar(25) DEFAULT NULL,
  `payme_time` varchar(25) DEFAULT NULL,
  `cancel_time` varchar(25) DEFAULT NULL,
  `create_time` varchar(25) DEFAULT NULL,
  `perform_time` varchar(25) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `payme_uz`
--

INSERT INTO `payme_uz` (`id`, `transaction`, `code`, `state`, `owner_id`, `amount`, `reason`, `payme_time`, `cancel_time`, `create_time`, `perform_time`) VALUES
(45, '5a85287a1edd61437b48cecb', NULL, '-2', '20', '1000', '5', '1518676090596', '1518676162000', '1518676090000', '1518676098000');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `payme_uz`
--
ALTER TABLE `payme_uz`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `payme_uz`
--
ALTER TABLE `payme_uz`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
