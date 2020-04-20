CREATE DATABASE IF NOT EXISTS api_rest_videos_symfony CHARACTER SET utf8 COLLATE utf8_general_ci;
USE api_rest_videos_symfony;

--
-- Estructura de tabla para la tabla `videos`
--

DROP TABLE IF EXISTS `videos`;
CREATE TABLE IF NOT EXISTS `videos` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `user_id` int(255) NOT NULL ,
  -- `category_id` int(255) NOT NULL ,
  -- `price` double(6,2) NOT NULL ,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `url` varchar(255) NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  -- `image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT pk_videos PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Estructura de tabla para la tabla `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `surname` varchar(100) DEFAULT NULL,
  `nick` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT NULL,
  -- `description` text DEFAULT NULL,
  -- `image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  -- `updated_at` datetime DEFAULT NULL,
  -- `remember_token` varchar(255) DEFAULT NULL,
  CONSTRAINT pk_users PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Restricciones para las tablas
--
ALTER TABLE `users`
    ADD CONSTRAINT uk_users_email_nick UNIQUE(email, nick);

ALTER TABLE `videos`
    ADD CONSTRAINT uk_videos_name UNIQUE(title),
    ADD CONSTRAINT `fk_video_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE restrict ON UPDATE restrict;

--
-- Volcado de datos para las tablas
--

INSERT INTO `users` (`id`, `name`, `surname`, `nick`, `email`, `password`, `role`, `created_at`) VALUES
(NULL, 'Javier', 'Estrada', '@javier', 'admin@admin.com', 'pass12PASS', 'ROLE_ADMIN', '2020-03-04 21:13:35');

-- (NULL, 'Javier', 'Estrada', 'ROLE_ADMIN', '@admin', 'admin@admin.com', '$2y$12$/KpQiMmVlvKXFTCZOQxtX.rilC7/bAONlGKtJ7vZJWv/KrM9EwSbu', 'descripción del administrador', null, '2020-03-04 21:13:35', null, null);

INSERT INTO `videos` (`id`, `user_id`, `title`, `description`, `url`, `status`, `created_at`, `updated_at`) VALUES
(NULL, 1, 'Angels Robbie Williams','Video del concierto de Taylor Swift', 'https://www.youtube.com/watch?v=kZuI6yv5mkQ', 'favorito', '2020-03-04 21:13:35', '2020-03-04 21:13:35'),
(NULL, 1, 'The show must go on','Queen in concert', 'https://www.youtube.com/watch?v=CQAT5qdG8tI', 'conciertos', '2020-03-04 21:13:35', '2020-03-04 21:13:35');

