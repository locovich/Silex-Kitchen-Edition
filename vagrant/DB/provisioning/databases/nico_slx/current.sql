CREATE TABLE `tracker` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session` varchar(26) COLLATE utf8_unicode_ci NOT NULL,
  `gateway_id` int(26) DEFAULT NULL,
  `ip` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `ua` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
  `source` varchar(56) COLLATE utf8_unicode_ci NOT NULL,
  `page` int(11) NOT NULL DEFAULT '1',
  `gateway` varchar(56) COLLATE utf8_unicode_ci NOT NULL,
  `status` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `buyer_email` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `index_session` (`session`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci