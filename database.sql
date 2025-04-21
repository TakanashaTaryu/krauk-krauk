

  SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
  START TRANSACTION;
  SET time_zone = "+00:00";

  CREATE TABLE `akun` (
    `id` int NOT NULL,
    `email` varchar(255) NOT NULL,
    `password` varchar(255) NOT NULL,
    `admin_value` tinyint(1) NOT NULL DEFAULT '0',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `no_telp` varchar(20) DEFAULT NULL
  ) ;

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


  CREATE TABLE `pesanan` (
    `id` int NOT NULL,
    `id_customer` int NOT NULL,
    `total_harga` decimal(10,2) NOT NULL,
    `status` enum('Menunggu Konfirmasi','Diterima','Diproses','Diperjalanan') NOT NULL DEFAULT 'Menunggu Konfirmasi',
    `bukti_pembayaran` varchar(255) DEFAULT NULL,
    `waktu_pemesanan` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `nama_pemesan` varchar(100) NOT NULL,
    `alamat_pemesan` text NOT NULL
  ) ;


  CREATE TABLE `pesanan_detail` (
    `id` int NOT NULL,
    `id_pesanan` int NOT NULL,
    `id_menu` int NOT NULL,
    `jumlah` int NOT NULL DEFAULT '1',
    `harga_satuan` decimal(10,2) NOT NULL
  ) ;

  ALTER TABLE `akun`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `email` (`email`);


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
    MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;


  ALTER TABLE `keranjang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;


  ALTER TABLE `menu`
    MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;


  ALTER TABLE `pesanan`
    MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;


  ALTER TABLE `pesanan_detail`
    MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

  ALTER TABLE `keranjang`
    ADD CONSTRAINT `keranjang_ibfk_1` FOREIGN KEY (`id_customer`) REFERENCES `akun` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `keranjang_ibfk_2` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id`) ON DELETE CASCADE;


  ALTER TABLE `pesanan`
    ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`id_customer`) REFERENCES `akun` (`id`) ON DELETE CASCADE;

  ALTER TABLE `pesanan_detail`
    ADD CONSTRAINT `pesanan_detail_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `pesanan_detail_ibfk_2` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id`) ON DELETE CASCADE;
  
  ALTER TABLE pesanan 
  ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER alamat_pemesan,
  ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude;
  COMMIT;
