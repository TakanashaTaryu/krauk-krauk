CREATE TABLE `akun` (
  `id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `admin_value` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `no_telp` varchar(20) DEFAULT NULL
) ;


INSERT INTO `akun` (`id`, `email`, `password`, `admin_value`, `created_at`, `no_telp`) VALUES
(2, 'fiqrifirmansyah15@gmail.com', '$2y$10$BYCFaa/s3rfITHs8MIiEJ.MhgREnskiZs/a7NbuCt9Vym5Z2MuWiy', 0, '2025-04-18 03:22:09', NULL),
(3, 'aa@mail', '$2y$10$lfc0CbowZepZtWpk7L6dleTncldpswajGIJ7/FPXX6L/NUpmztwSi', 1, '2025-04-18 03:30:22', NULL),
(4, 'tatsuarieyu@gmail.com', '$2y$10$YCaydGiWLtF9fBA0m.eGP.Mq3Fo2y13l5/AFBpg8UbPqrPa5d85MO', 0, '2025-04-18 05:33:22', '082192986904'),
(5, 'aku@gmail.com', '$2y$10$ACJGmzll4zOrw7PnNpj1GuorV/KtEhGAJmNiCG26DMDo.rtzJMpAe', 0, '2025-04-21 08:06:23', '082192986904'),
(6, 'akunxptr@gmail.com', '$2y$10$RwqLWB7WIPAxuXz3nPqKieyrzkD/Ep8lSeYlX05Oq2Nig47qbsLlO', 0, '2025-04-21 08:08:46', '082238264823'),
(7, 'ea@mail.co', '$2y$10$wCwBJiUCOCbHmbxpLXHvzuYYGVwM6iT0tLEBzaHxL8.E7JiJce0XC', 0, '2025-04-21 17:04:51', '124234234');


CREATE TABLE `feedback` (
  `id` int NOT NULL,
  `id_pesanan` int NOT NULL,
  `rating` int NOT NULL,
  `komentar` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;


INSERT INTO `feedback` (`id`, `id_pesanan`, `rating`, `komentar`, `created_at`) VALUES
(3, 22, 5, 'esdsdvsfv', '2025-04-21 17:49:53'),
(4, 25, 5, 'aaaaa', '2025-04-21 18:10:31');


CREATE TABLE `keranjang` (
  `id` int NOT NULL,
  `id_customer` int NOT NULL,
  `id_menu` int NOT NULL,
  `jumlah` int NOT NULL DEFAULT '1'
) ;


CREATE TABLE `menu` (
  `id` int NOT NULL,
  `nama` varchar(255) NOT NULL,
  `deskripsi` text NOT NULL,
  `gambar` varchar(255) NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `stok` int NOT NULL DEFAULT '0'
) ;


INSERT INTO `menu` (`id`, `nama`, `deskripsi`, `gambar`, `harga`, `stok`) VALUES
(1, 'Pancake', 'pancake yang lembut', '6801dba9d3e47.jpg', 20000.00, 9897),
(2, 'Steak', 'Steak sapi yang langsung dari perternakan terbaik', '6801dbe1ca882.jpg', 230000.00, 0),
(3, 'Terserah Saya', 'menu yang cocok untuk pasangan kamu', '6801dbeb687c9.jpg', 20000.00, 12),
(4, 'Susu Murni', 'nasional', '6801dbc2cb4d0.jpg', 5000.00, 29),
(5, 'Es Teh Panas', 'panas dingin', '6801db86a95e1.jpg', 8000.00, 93);


CREATE TABLE `pesanan` (
  `id` int NOT NULL,
  `id_customer` int NOT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `status` enum('Menunggu Konfirmasi','Diterima','Diproses','Diperjalanan','Telah Sampai','Dibatalkan Olen Penjual','Gagal') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Menunggu Konfirmasi',
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `waktu_pemesanan` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nama_pemesan` varchar(100) NOT NULL,
  `alamat_pemesan` text NOT NULL,
  `notes` text,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ;


INSERT INTO `pesanan` (`id`, `id_customer`, `total_harga`, `status`, `bukti_pembayaran`, `waktu_pemesanan`, `nama_pemesan`, `alamat_pemesan`, `notes`, `latitude`, `longitude`) VALUES
(1, 2, 35000.00, 'Telah Sampai', '6801c599abb3e.png', '2025-04-18 03:22:48', '', '', NULL, NULL, NULL),
(7, 4, 20000.00, 'Diproses', NULL, '2025-04-18 07:22:11', 'a', 'a', NULL, NULL, NULL),
(8, 4, 270000.00, 'Diproses', NULL, '2025-04-18 07:22:41', 'a', 'a', NULL, NULL, NULL),
(9, 4, 513000.00, 'Diproses', NULL, '2025-04-18 07:27:09', 'aa', 'aa', NULL, NULL, NULL),
(10, 2, 250000.00, 'Diproses', NULL, '2025-04-18 07:39:29', 'a', 'a', NULL, NULL, NULL),
(11, 2, 20000.00, 'Telah Sampai', NULL, '2025-04-18 07:41:52', 'a', 'a', NULL, NULL, NULL),
(12, 2, 230000.00, 'Telah Sampai', '680205e2c648b.jpg', '2025-04-18 07:57:22', 'nfhj', 'uigk\r\n', NULL, NULL, NULL),
(13, 2, 278000.00, 'Diproses', '68038a32e8e42.jpg', '2025-03-14 11:34:10', 'anu', 'gatau dimana', NULL, NULL, NULL),
(14, 2, 250000.00, 'Diproses', '6803939de3006.jpg', '2025-04-19 12:14:21', 'au', 'au', NULL, NULL, NULL),
(15, 2, 28000.00, 'Diproses', '6804abfeb75a2.jpg', '2025-04-20 08:10:38', 'ini saya', 'entah dimana\r\n', NULL, NULL, NULL),
(16, 5, 270000.00, 'Diproses', '6805fd188b489.png', '2025-04-21 08:08:56', 'Ea', 'Alamat\r\n', NULL, NULL, NULL),
(17, 6, 258000.00, 'Diproses', '6805fe4b4ac78.png', '2025-04-21 08:14:03', 'Luqman', 'TULT', NULL, NULL, NULL),
(20, 5, 460800.00, 'Diperjalanan', '68063ea0bc3f7.png', '2025-04-21 12:48:32', 'anjay', 'inih menu', NULL, -6.20000000, 106.81666600),
(22, 7, 270800.00, 'Telah Sampai', '68067d0ee93a9.png', '2025-04-21 17:14:54', 'dwewedf', 'easdafwaf', NULL, -6.20000000, 106.81666600),
(23, 7, 2858000.00, 'Menunggu Konfirmasi', NULL, '2025-04-21 18:02:12', 'ryu', 'asadwd', NULL, -6.98016854, 107.63313610),
(24, 7, 230000.00, 'Menunggu Konfirmasi', NULL, '2025-04-21 18:03:25', 'adqwa', 'wad', NULL, -6.20000000, 106.81666600),
(25, 7, 250800.00, 'Telah Sampai', '68068999ed230.png', '2025-04-21 18:08:25', 'nama', 'ALAMAT', 'inih notess', -6.20000000, 106.81666600);


CREATE TABLE `pesanan_detail` (
  `id` int NOT NULL,
  `id_pesanan` int NOT NULL,
  `id_menu` int NOT NULL,
  `jumlah` int NOT NULL DEFAULT '1',
  `harga_satuan` decimal(10,2) NOT NULL
) ;


INSERT INTO `pesanan_detail` (`id`, `id_pesanan`, `id_menu`, `jumlah`, `harga_satuan`) VALUES
(1, 1, 2, 1, 35000.00),
(6, 7, 1, 1, 20000.00),
(7, 8, 2, 1, 230000.00),
(8, 8, 3, 1, 20000.00),
(9, 8, 1, 1, 20000.00),
(10, 9, 2, 2, 230000.00),
(11, 9, 1, 1, 20000.00),
(12, 9, 5, 1, 8000.00),
(13, 9, 3, 1, 20000.00),
(14, 9, 4, 1, 5000.00),
(15, 10, 1, 1, 20000.00),
(16, 10, 2, 1, 230000.00),
(17, 11, 3, 1, 20000.00),
(18, 12, 2, 1, 230000.00),
(19, 13, 3, 1, 20000.00),
(20, 13, 2, 1, 230000.00),
(21, 13, 5, 1, 8000.00),
(22, 13, 1, 1, 20000.00),
(23, 14, 2, 1, 230000.00),
(24, 14, 3, 1, 20000.00),
(25, 15, 5, 1, 8000.00),
(26, 15, 3, 1, 20000.00),
(27, 16, 3, 1, 20000.00),
(28, 16, 2, 1, 230000.00),
(29, 16, 1, 1, 20000.00),
(30, 17, 2, 1, 230000.00),
(31, 17, 3, 1, 20000.00),
(32, 17, 5, 1, 8000.00),
(38, 20, 2, 2, 230000.00),
(44, 22, 2, 1, 230000.00),
(45, 22, 3, 2, 20000.00),
(46, 23, 1, 1, 20000.00),
(47, 23, 2, 11, 230000.00),
(48, 23, 3, 1, 20000.00),
(49, 23, 4, 56, 5000.00),
(50, 23, 5, 1, 8000.00),
(51, 24, 2, 1, 230000.00),
(52, 25, 2, 1, 230000.00),
(53, 25, 3, 1, 20000.00);

ALTER TABLE `akun`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pesanan` (`id_pesanan`);

ALTER TABLE `keranjang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_customer` (`id_customer`),
  ADD KEY `id_menu` (`id_menu`);

ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_customer` (`id_customer`);

ALTER TABLE `pesanan_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pesanan` (`id_pesanan`),
  ADD KEY `id_menu` (`id_menu`);

ALTER TABLE `akun`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

ALTER TABLE `feedback`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `keranjang`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

ALTER TABLE `menu`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `pesanan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

ALTER TABLE `pesanan_detail`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE;


ALTER TABLE `keranjang`
  ADD CONSTRAINT `keranjang_ibfk_1` FOREIGN KEY (`id_customer`) REFERENCES `akun` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `keranjang_ibfk_2` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id`) ON DELETE CASCADE;


ALTER TABLE `pesanan`
  ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`id_customer`) REFERENCES `akun` (`id`) ON DELETE CASCADE;


ALTER TABLE `pesanan_detail`
  ADD CONSTRAINT `pesanan_detail_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pesanan_detail_ibfk_2` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id`) ON DELETE CASCADE;

-- Chat conversations table
CREATE TABLE `chat_conversations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_pesanan` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_pesanan` (`id_pesanan`),
  CONSTRAINT `chat_conversations_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE
);

-- Chat messages table
CREATE TABLE `chat_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_conversation` int NOT NULL,
  `sender_id` int NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `id_conversation` (`id_conversation`),
  KEY `sender_id` (`sender_id`),
  CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`id_conversation`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `akun` (`id`) ON DELETE CASCADE
);

-- Add-ons tables
CREATE TABLE `menu_add_ons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_menu` int NOT NULL,
  `nama` varchar(255) NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_menu` (`id_menu`),
  CONSTRAINT `menu_add_ons_ibfk_1` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id`) ON DELETE CASCADE
);

CREATE TABLE `keranjang_add_ons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_keranjang` int NOT NULL,
  `id_add_on` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_keranjang` (`id_keranjang`),
  KEY `id_add_on` (`id_add_on`),
  CONSTRAINT `keranjang_add_ons_ibfk_1` FOREIGN KEY (`id_keranjang`) REFERENCES `keranjang` (`id`) ON DELETE CASCADE,
  CONSTRAINT `keranjang_add_ons_ibfk_2` FOREIGN KEY (`id_add_on`) REFERENCES `menu_add_ons` (`id`) ON DELETE CASCADE
);

CREATE TABLE `pesanan_detail_add_ons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_pesanan_detail` int NOT NULL,
  `nama` varchar(255) NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_pesanan_detail` (`id_pesanan_detail`),
  CONSTRAINT `pesanan_detail_add_ons_ibfk_1` FOREIGN KEY (`id_pesanan_detail`) REFERENCES `pesanan_detail` (`id`) ON DELETE CASCADE
);
