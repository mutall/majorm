-- phpMyAdmin SQL Dump
-- version 4.3.11
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: May 25, 2019 at 11:07 AM
-- Server version: 5.6.24
-- PHP Version: 5.6.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `mutallco_majorm`
--

-- --------------------------------------------------------

--
-- Table structure for table `adjustment`
--

CREATE TABLE IF NOT EXISTS `adjustment` (
  `adjustment` int(11) NOT NULL,
  `client` int(11) NOT NULL,
  `date` date NOT NULL,
  `reason` varchar(255) NOT NULL,
  `amount` double DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `adjustment`
--

INSERT INTO `adjustment` (`adjustment`, `client`, `date`, `reason`, `amount`, `timestamp`) VALUES
(1, 1, '2019-05-23', 'Wrong meter reading', -20000, '2019-05-25 07:45:43');

-- --------------------------------------------------------

--
-- Table structure for table `charge`
--

CREATE TABLE IF NOT EXISTS `charge` (
  `charge` int(11) NOT NULL,
  `service` int(11) NOT NULL,
  `wconnection` int(11) NOT NULL,
  `invoice` int(11) NOT NULL,
  `amount` double DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `charge`
--

INSERT INTO `charge` (`charge`, `service`, `wconnection`, `invoice`, `amount`) VALUES
(1, 1, 1, 2, 130);

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE IF NOT EXISTS `client` (
  `client` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `vendor` int(11) DEFAULT NULL,
  `zone` int(11) DEFAULT NULL,
  `code` varchar(30) DEFAULT NULL,
  `valid` int(1) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `client`
--

INSERT INTO `client` (`client`, `user`, `vendor`, `zone`, `code`, `valid`) VALUES
(1, 2, 1, 1, 'PM', 1);

-- --------------------------------------------------------

--
-- Table structure for table `closing_balance`
--

CREATE TABLE IF NOT EXISTS `closing_balance` (
  `closing_balance` int(11) NOT NULL,
  `invoice` int(11) NOT NULL,
  `amount` double DEFAULT NULL,
  `initial` int(1) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `closing_balance`
--

INSERT INTO `closing_balance` (`closing_balance`, `invoice`, `amount`, `initial`) VALUES
(1, 1, 100, 1),
(2, 2, 22430, 0);

-- --------------------------------------------------------

--
-- Table structure for table `invoice`
--

CREATE TABLE IF NOT EXISTS `invoice` (
  `invoice` int(11) NOT NULL,
  `client` int(11) NOT NULL,
  `invoice_1` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ref` varchar(30) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `invoice`
--

INSERT INTO `invoice` (`invoice`, `client`, `invoice_1`, `timestamp`, `ref`) VALUES
(1, 1, NULL, '2019-04-30 21:00:00', NULL),
(2, 1, 1, '2019-05-25 07:46:43', 'PM-2019-05-25 10:46:43');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE IF NOT EXISTS `payment` (
  `payment` int(11) NOT NULL,
  `client` int(11) NOT NULL,
  `date` date NOT NULL,
  `bank` varchar(30) DEFAULT NULL,
  `type` varchar(30) DEFAULT NULL,
  `ref` varchar(30) NOT NULL,
  `amount` double DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment`, `client`, `date`, `bank`, `type`, `ref`, `amount`, `timestamp`) VALUES
(1, 1, '2019-05-17', 'COOP', 'Mpesa', '09988TRT77888', -40000, '2019-05-25 07:38:15'),
(2, 1, '2019-05-20', 'KCB', 'Cheque', '0009876', -140000, '2019-05-25 07:43:16');

-- --------------------------------------------------------

--
-- Table structure for table `reader`
--

CREATE TABLE IF NOT EXISTS `reader` (
  `reader` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `valid` int(1) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `reader`
--

INSERT INTO `reader` (`reader`, `user`, `valid`) VALUES
(1, 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `service`
--

CREATE TABLE IF NOT EXISTS `service` (
  `service` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `price` double DEFAULT NULL,
  `auto` int(10) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `service`
--

INSERT INTO `service` (`service`, `name`, `description`, `price`, `auto`) VALUES
(1, 'Standing charge', 'Sting charges for each meter connection', 130, 1);

-- --------------------------------------------------------

--
-- Table structure for table `state`
--

CREATE TABLE IF NOT EXISTS `state` (
  `state` int(11) NOT NULL,
  `wconnection` int(11) NOT NULL,
  `date` date NOT NULL,
  `disconnection` int(1) NOT NULL,
  `wreading` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `subscription`
--

CREATE TABLE IF NOT EXISTS `subscription` (
  `subscription` int(11) NOT NULL,
  `wconnection` int(11) NOT NULL,
  `service` int(11) NOT NULL,
  `amount` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `user` int(11) NOT NULL,
  `name` varchar(30) NOT NULL,
  `password` varchar(30) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(30) DEFAULT NULL,
  `id_no` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user`, `name`, `password`, `phone`, `email`, `id_no`, `address`) VALUES
(1, 'Major M', 'mm', '2334556', 'mm@gmail.om', '345678', 'P. O. Box 374, Matasia'),
(2, 'Muraya P.', 'pm', '787709777', 'pm@gmail.com', '900998', 'Box 223, Kiserian'),
(3, 'Elias Msafiri', 'EM', '999988', 'em@gmail.com', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vendor`
--

CREATE TABLE IF NOT EXISTS `vendor` (
  `vendor` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `valid` int(1) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `price` double DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `vendor`
--

INSERT INTO `vendor` (`vendor`, `user`, `valid`, `code`, `price`) VALUES
(1, 1, 1, 'MM', 200);

-- --------------------------------------------------------

--
-- Table structure for table `wconnection`
--

CREATE TABLE IF NOT EXISTS `wconnection` (
  `wconnection` int(11) NOT NULL,
  `client` int(11) NOT NULL,
  `meter_no` varchar(30) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `wconnection`
--

INSERT INTO `wconnection` (`wconnection`, `client`, `meter_no`, `name`, `end_date`, `latitude`, `longitude`) VALUES
(1, 1, '23456-6778', '???', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wconsumption`
--

CREATE TABLE IF NOT EXISTS `wconsumption` (
  `wconsumption` int(11) NOT NULL,
  `wconnection` int(11) NOT NULL,
  `invoice` int(11) NOT NULL,
  `prev_date` date DEFAULT NULL,
  `prev_value` int(5) DEFAULT NULL,
  `curr_value` int(5) DEFAULT NULL,
  `curr_date` date DEFAULT NULL,
  `price` double DEFAULT NULL,
  `units` int(5) DEFAULT NULL,
  `amount` double DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `wconsumption`
--

INSERT INTO `wconsumption` (`wconsumption`, `wconnection`, `invoice`, `prev_date`, `prev_value`, `curr_value`, `curr_date`, `price`, `units`, `amount`) VALUES
(1, 1, 2, '2019-05-02', 3456, 4567, '2019-05-14', 200, 1111, 222200);

-- --------------------------------------------------------

--
-- Table structure for table `wreading`
--

CREATE TABLE IF NOT EXISTS `wreading` (
  `wreading` int(11) NOT NULL,
  `wconnection` int(11) NOT NULL,
  `reader` int(11) DEFAULT NULL,
  `date` date NOT NULL,
  `value` int(10) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `wreading`
--

INSERT INTO `wreading` (`wreading`, `wconnection`, `reader`, `date`, `value`, `latitude`, `longitude`, `timestamp`) VALUES
(1, 1, NULL, '2019-05-02', 3456, NULL, NULL, '2019-05-25 06:56:31'),
(2, 1, NULL, '2019-05-14', 4567, NULL, NULL, '2019-05-25 07:35:52'),
(3, 1, 1, '2019-05-21', 4600, NULL, NULL, '2019-05-25 07:50:31'),
(4, 1, NULL, '2019-05-22', 4610, NULL, NULL, '2019-05-25 07:53:17');

-- --------------------------------------------------------

--
-- Table structure for table `zone`
--

CREATE TABLE IF NOT EXISTS `zone` (
  `zone` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `demarcation` varchar(255) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `zone`
--

INSERT INTO `zone` (`zone`, `name`, `code`, `demarcation`) VALUES
(1, 'Kiserian Town', 'KIS', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `adjustment`
--
ALTER TABLE `adjustment`
  ADD PRIMARY KEY (`adjustment`), ADD UNIQUE KEY `id1` (`client`,`date`,`reason`);

--
-- Indexes for table `charge`
--
ALTER TABLE `charge`
  ADD PRIMARY KEY (`charge`), ADD UNIQUE KEY `id2` (`wconnection`,`service`,`invoice`), ADD KEY `service` (`service`), ADD KEY `invoice` (`invoice`);

--
-- Indexes for table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`client`), ADD UNIQUE KEY `id3` (`user`,`valid`), ADD KEY `vendor` (`vendor`), ADD KEY `zone` (`zone`);

--
-- Indexes for table `closing_balance`
--
ALTER TABLE `closing_balance`
  ADD PRIMARY KEY (`closing_balance`), ADD UNIQUE KEY `id4` (`invoice`);

--
-- Indexes for table `invoice`
--
ALTER TABLE `invoice`
  ADD PRIMARY KEY (`invoice`), ADD UNIQUE KEY `id5` (`client`,`timestamp`), ADD KEY `invoice_1` (`invoice_1`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment`), ADD UNIQUE KEY `id6` (`client`,`date`,`ref`);

--
-- Indexes for table `reader`
--
ALTER TABLE `reader`
  ADD PRIMARY KEY (`reader`), ADD UNIQUE KEY `id7` (`user`,`valid`);

--
-- Indexes for table `service`
--
ALTER TABLE `service`
  ADD PRIMARY KEY (`service`), ADD UNIQUE KEY `id8` (`name`);

--
-- Indexes for table `state`
--
ALTER TABLE `state`
  ADD PRIMARY KEY (`state`), ADD UNIQUE KEY `id9` (`wconnection`,`date`,`disconnection`), ADD KEY `wreading` (`wreading`);

--
-- Indexes for table `subscription`
--
ALTER TABLE `subscription`
  ADD PRIMARY KEY (`subscription`), ADD UNIQUE KEY `id10` (`wconnection`,`service`), ADD KEY `service` (`service`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user`), ADD UNIQUE KEY `id11` (`name`);

--
-- Indexes for table `vendor`
--
ALTER TABLE `vendor`
  ADD PRIMARY KEY (`vendor`), ADD UNIQUE KEY `id12` (`user`,`valid`);

--
-- Indexes for table `wconnection`
--
ALTER TABLE `wconnection`
  ADD PRIMARY KEY (`wconnection`), ADD UNIQUE KEY `id13` (`client`,`meter_no`);

--
-- Indexes for table `wconsumption`
--
ALTER TABLE `wconsumption`
  ADD PRIMARY KEY (`wconsumption`), ADD UNIQUE KEY `id14` (`wconnection`,`invoice`), ADD KEY `invoice` (`invoice`);

--
-- Indexes for table `wreading`
--
ALTER TABLE `wreading`
  ADD PRIMARY KEY (`wreading`), ADD UNIQUE KEY `id15` (`date`,`wconnection`), ADD KEY `wconnection` (`wconnection`), ADD KEY `reader` (`reader`);

--
-- Indexes for table `zone`
--
ALTER TABLE `zone`
  ADD PRIMARY KEY (`zone`), ADD UNIQUE KEY `id16` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `adjustment`
--
ALTER TABLE `adjustment`
  MODIFY `adjustment` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `charge`
--
ALTER TABLE `charge`
  MODIFY `charge` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `client`
--
ALTER TABLE `client`
  MODIFY `client` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `closing_balance`
--
ALTER TABLE `closing_balance`
  MODIFY `closing_balance` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `invoice`
--
ALTER TABLE `invoice`
  MODIFY `invoice` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `reader`
--
ALTER TABLE `reader`
  MODIFY `reader` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `service`
--
ALTER TABLE `service`
  MODIFY `service` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `state`
--
ALTER TABLE `state`
  MODIFY `state` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `subscription`
--
ALTER TABLE `subscription`
  MODIFY `subscription` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `vendor`
--
ALTER TABLE `vendor`
  MODIFY `vendor` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `wconnection`
--
ALTER TABLE `wconnection`
  MODIFY `wconnection` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `wconsumption`
--
ALTER TABLE `wconsumption`
  MODIFY `wconsumption` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `wreading`
--
ALTER TABLE `wreading`
  MODIFY `wreading` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `zone`
--
ALTER TABLE `zone`
  MODIFY `zone` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `adjustment`
--
ALTER TABLE `adjustment`
ADD CONSTRAINT `adjustment_ibfk_1` FOREIGN KEY (`client`) REFERENCES `client` (`client`);

--
-- Constraints for table `charge`
--
ALTER TABLE `charge`
ADD CONSTRAINT `charge_ibfk_1` FOREIGN KEY (`service`) REFERENCES `service` (`service`),
ADD CONSTRAINT `charge_ibfk_2` FOREIGN KEY (`wconnection`) REFERENCES `wconnection` (`wconnection`),
ADD CONSTRAINT `charge_ibfk_3` FOREIGN KEY (`invoice`) REFERENCES `invoice` (`invoice`);

--
-- Constraints for table `client`
--
ALTER TABLE `client`
ADD CONSTRAINT `client_ibfk_1` FOREIGN KEY (`user`) REFERENCES `user` (`user`),
ADD CONSTRAINT `client_ibfk_2` FOREIGN KEY (`vendor`) REFERENCES `vendor` (`vendor`),
ADD CONSTRAINT `client_ibfk_3` FOREIGN KEY (`zone`) REFERENCES `zone` (`zone`);

--
-- Constraints for table `closing_balance`
--
ALTER TABLE `closing_balance`
ADD CONSTRAINT `closing_balance_ibfk_1` FOREIGN KEY (`invoice`) REFERENCES `invoice` (`invoice`);

--
-- Constraints for table `invoice`
--
ALTER TABLE `invoice`
ADD CONSTRAINT `invoice_ibfk_1` FOREIGN KEY (`client`) REFERENCES `client` (`client`),
ADD CONSTRAINT `invoice_ibfk_2` FOREIGN KEY (`invoice_1`) REFERENCES `invoice` (`invoice`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`client`) REFERENCES `client` (`client`);

--
-- Constraints for table `reader`
--
ALTER TABLE `reader`
ADD CONSTRAINT `reader_ibfk_1` FOREIGN KEY (`user`) REFERENCES `user` (`user`);

--
-- Constraints for table `state`
--
ALTER TABLE `state`
ADD CONSTRAINT `state_ibfk_1` FOREIGN KEY (`wconnection`) REFERENCES `wconnection` (`wconnection`),
ADD CONSTRAINT `state_ibfk_2` FOREIGN KEY (`wreading`) REFERENCES `wreading` (`wreading`);

--
-- Constraints for table `subscription`
--
ALTER TABLE `subscription`
ADD CONSTRAINT `subscription_ibfk_1` FOREIGN KEY (`wconnection`) REFERENCES `wconnection` (`wconnection`),
ADD CONSTRAINT `subscription_ibfk_2` FOREIGN KEY (`service`) REFERENCES `service` (`service`);

--
-- Constraints for table `vendor`
--
ALTER TABLE `vendor`
ADD CONSTRAINT `vendor_ibfk_1` FOREIGN KEY (`user`) REFERENCES `user` (`user`);

--
-- Constraints for table `wconnection`
--
ALTER TABLE `wconnection`
ADD CONSTRAINT `wconnection_ibfk_1` FOREIGN KEY (`client`) REFERENCES `client` (`client`);

--
-- Constraints for table `wconsumption`
--
ALTER TABLE `wconsumption`
ADD CONSTRAINT `wconsumption_ibfk_1` FOREIGN KEY (`wconnection`) REFERENCES `wconnection` (`wconnection`),
ADD CONSTRAINT `wconsumption_ibfk_2` FOREIGN KEY (`invoice`) REFERENCES `invoice` (`invoice`);

--
-- Constraints for table `wreading`
--
ALTER TABLE `wreading`
ADD CONSTRAINT `wreading_ibfk_1` FOREIGN KEY (`wconnection`) REFERENCES `wconnection` (`wconnection`),
ADD CONSTRAINT `wreading_ibfk_2` FOREIGN KEY (`reader`) REFERENCES `reader` (`reader`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
