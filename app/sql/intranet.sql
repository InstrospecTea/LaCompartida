-- phpMyAdmin SQL Dump
-- version 2.6.4-pl2-Debian-1
-- http://www.phpmyadmin.net
-- 
-- Servidor: localhost
-- Tiempo de generación: 23-02-2006 a las 11:24:07
-- Versión del servidor: 4.1.14
-- Versión de PHP: 4.4.0-4
-- 
-- Base de datos: `intranet`
-- 

-- --------------------------------------------------------

-- 
-- Estructura de tabla para la tabla `comuna`
-- 

CREATE TABLE `comuna` (
  `id_comuna` int(11) NOT NULL default '0',
  `glosa_comuna` char(64) NOT NULL default '',
  `region` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id_comuna`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- Volcar la base de datos para la tabla `comuna`
-- 

INSERT INTO `comuna` VALUES (1101, 'Iquique', 0);
INSERT INTO `comuna` VALUES (1102, 'Camiña', 0);
INSERT INTO `comuna` VALUES (1103, 'Colchane', 0);
INSERT INTO `comuna` VALUES (1104, 'Huara', 0);
INSERT INTO `comuna` VALUES (1105, 'Pica', 0);
INSERT INTO `comuna` VALUES (1106, 'Pozo Almonte', 0);
INSERT INTO `comuna` VALUES (1201, 'Arica', 0);
INSERT INTO `comuna` VALUES (1202, 'Camarones', 0);
INSERT INTO `comuna` VALUES (1301, 'Putre', 0);
INSERT INTO `comuna` VALUES (1302, 'General Lagos', 0);
INSERT INTO `comuna` VALUES (2101, 'Antofagasta', 0);
INSERT INTO `comuna` VALUES (2102, 'Mejillones', 0);
INSERT INTO `comuna` VALUES (2103, 'Sierra', 0);
INSERT INTO `comuna` VALUES (2104, 'Taltal', 0);
INSERT INTO `comuna` VALUES (2201, 'Calama', 0);
INSERT INTO `comuna` VALUES (2202, 'Ollagüe', 0);
INSERT INTO `comuna` VALUES (2203, 'San Pedro de Atacama', 0);
INSERT INTO `comuna` VALUES (2301, 'Tocopilla', 0);
INSERT INTO `comuna` VALUES (2302, 'María Elena', 0);
INSERT INTO `comuna` VALUES (3101, 'Copiapó', 0);
INSERT INTO `comuna` VALUES (3102, 'Caldera', 0);
INSERT INTO `comuna` VALUES (3103, 'Tierra Amarilla', 0);
INSERT INTO `comuna` VALUES (3201, 'Chañaral', 0);
INSERT INTO `comuna` VALUES (3202, 'Diego de Almagro', 0);
INSERT INTO `comuna` VALUES (3301, 'Vallenar', 0);
INSERT INTO `comuna` VALUES (3302, 'Alto del Carmen', 0);
INSERT INTO `comuna` VALUES (3303, 'Freirina', 0);
INSERT INTO `comuna` VALUES (3304, 'Huasco', 0);
INSERT INTO `comuna` VALUES (4101, 'La Serena', 0);
INSERT INTO `comuna` VALUES (4102, 'Coquimbo', 0);
INSERT INTO `comuna` VALUES (4103, 'Andacollo', 0);
INSERT INTO `comuna` VALUES (4104, 'La Higuera', 0);
INSERT INTO `comuna` VALUES (4105, 'Paiguano', 0);
INSERT INTO `comuna` VALUES (4106, 'Vicuña', 0);
INSERT INTO `comuna` VALUES (4201, 'Illapel', 0);
INSERT INTO `comuna` VALUES (4202, 'Canela', 0);
INSERT INTO `comuna` VALUES (4203, 'Los Vilos', 0);
INSERT INTO `comuna` VALUES (4204, 'Salamanca', 0);
INSERT INTO `comuna` VALUES (4301, 'Ovalle', 0);
INSERT INTO `comuna` VALUES (4302, 'Combarbalá', 0);
INSERT INTO `comuna` VALUES (4303, 'Monte Patria', 0);
INSERT INTO `comuna` VALUES (4304, 'Punitaqui', 0);
INSERT INTO `comuna` VALUES (4305, 'Río Hurtado', 0);
INSERT INTO `comuna` VALUES (5101, 'Valparaíso', 0);
INSERT INTO `comuna` VALUES (5102, 'Casablanca', 0);
INSERT INTO `comuna` VALUES (5103, 'Concon', 0);
INSERT INTO `comuna` VALUES (5104, 'Juan Fernández', 0);
INSERT INTO `comuna` VALUES (5105, 'Puchuncaví', 0);
INSERT INTO `comuna` VALUES (5106, 'Quilpué', 0);
INSERT INTO `comuna` VALUES (5107, 'Quintero', 0);
INSERT INTO `comuna` VALUES (5108, 'Villa Alemana', 0);
INSERT INTO `comuna` VALUES (5109, 'Viña del Mar', 0);
INSERT INTO `comuna` VALUES (5201, 'Isla de Pascua', 0);
INSERT INTO `comuna` VALUES (5301, 'Los Andes', 0);
INSERT INTO `comuna` VALUES (5302, 'Calle Larga', 0);
INSERT INTO `comuna` VALUES (5303, 'Rinconada', 0);
INSERT INTO `comuna` VALUES (5304, 'San Esteban', 0);
INSERT INTO `comuna` VALUES (5401, 'La Ligua', 0);
INSERT INTO `comuna` VALUES (5402, 'Cabildo', 0);
INSERT INTO `comuna` VALUES (5403, 'Papudo', 0);
INSERT INTO `comuna` VALUES (5404, 'Petorca', 0);
INSERT INTO `comuna` VALUES (5405, 'Zapallar', 0);
INSERT INTO `comuna` VALUES (5501, 'Quillota', 0);
INSERT INTO `comuna` VALUES (5502, 'Calera', 0);
INSERT INTO `comuna` VALUES (5503, 'Hijuelas', 0);
INSERT INTO `comuna` VALUES (5504, 'La Cruz', 0);
INSERT INTO `comuna` VALUES (5505, 'Limache', 0);
INSERT INTO `comuna` VALUES (5506, 'Nogales', 0);
INSERT INTO `comuna` VALUES (5507, 'Olmué', 0);
INSERT INTO `comuna` VALUES (5601, 'San Antonio', 0);
INSERT INTO `comuna` VALUES (5602, 'Algarrobo', 0);
INSERT INTO `comuna` VALUES (5603, 'Cartagena', 0);
INSERT INTO `comuna` VALUES (5604, 'El Quisco', 0);
INSERT INTO `comuna` VALUES (5605, 'El Tabo', 0);
INSERT INTO `comuna` VALUES (5606, 'Santo Domingo', 0);
INSERT INTO `comuna` VALUES (5701, 'San Felipe', 0);
INSERT INTO `comuna` VALUES (5702, 'Catemu', 0);
INSERT INTO `comuna` VALUES (5703, 'Llaillay', 0);
INSERT INTO `comuna` VALUES (5704, 'Panquehue', 0);
INSERT INTO `comuna` VALUES (5705, 'Putaendo', 0);
INSERT INTO `comuna` VALUES (5706, 'Santa María', 0);
INSERT INTO `comuna` VALUES (6101, 'Rancagua', 0);
INSERT INTO `comuna` VALUES (6102, 'Codegua', 0);
INSERT INTO `comuna` VALUES (6103, 'Coinco', 0);
INSERT INTO `comuna` VALUES (6104, 'Coltauco', 0);
INSERT INTO `comuna` VALUES (6105, 'Doñihue', 0);
INSERT INTO `comuna` VALUES (6106, 'Graneros', 0);
INSERT INTO `comuna` VALUES (6107, 'Las Cabras', 0);
INSERT INTO `comuna` VALUES (6108, 'Machalí', 0);
INSERT INTO `comuna` VALUES (6109, 'Malloa', 0);
INSERT INTO `comuna` VALUES (6110, 'Mostazal', 0);
INSERT INTO `comuna` VALUES (6111, 'Olivar', 0);
INSERT INTO `comuna` VALUES (6112, 'Peumo', 0);
INSERT INTO `comuna` VALUES (6113, 'Pichidegua', 0);
INSERT INTO `comuna` VALUES (6114, 'Quinta de Tilcoco', 0);
INSERT INTO `comuna` VALUES (6115, 'Rengo', 0);
INSERT INTO `comuna` VALUES (6116, 'Requínoa', 0);
INSERT INTO `comuna` VALUES (6117, 'San Vicente', 0);
INSERT INTO `comuna` VALUES (6201, 'Pichilemu', 0);
INSERT INTO `comuna` VALUES (6202, 'La Estrella', 0);
INSERT INTO `comuna` VALUES (6203, 'Litueche', 0);
INSERT INTO `comuna` VALUES (6204, 'Marchihue', 0);
INSERT INTO `comuna` VALUES (6205, 'Navidad', 0);
INSERT INTO `comuna` VALUES (6206, 'Paredones', 0);
INSERT INTO `comuna` VALUES (6301, 'San Fernando', 0);
INSERT INTO `comuna` VALUES (6302, 'Chepica', 0);
INSERT INTO `comuna` VALUES (6303, 'Chimbarongo', 0);
INSERT INTO `comuna` VALUES (6304, 'Lolol', 0);
INSERT INTO `comuna` VALUES (6305, 'Nancagua', 0);
INSERT INTO `comuna` VALUES (6306, 'Palmilla', 0);
INSERT INTO `comuna` VALUES (6307, 'Peralillo', 0);
INSERT INTO `comuna` VALUES (6308, 'Placilla', 0);
INSERT INTO `comuna` VALUES (6309, 'Pumanque', 0);
INSERT INTO `comuna` VALUES (6310, 'Santa Cruz', 0);
INSERT INTO `comuna` VALUES (7101, 'Talca', 0);
INSERT INTO `comuna` VALUES (7102, 'Constitución', 0);
INSERT INTO `comuna` VALUES (7103, 'Curepto', 0);
INSERT INTO `comuna` VALUES (7104, 'Empedrado', 0);
INSERT INTO `comuna` VALUES (7105, 'Maule', 0);
INSERT INTO `comuna` VALUES (7106, 'Pelarco', 0);
INSERT INTO `comuna` VALUES (7107, 'Pencahue', 0);
INSERT INTO `comuna` VALUES (7108, 'Río Claro', 0);
INSERT INTO `comuna` VALUES (7109, 'San Clemente', 0);
INSERT INTO `comuna` VALUES (7110, 'San Rafael', 0);
INSERT INTO `comuna` VALUES (7201, 'Cauquenes', 0);
INSERT INTO `comuna` VALUES (7202, 'Chanco', 0);
INSERT INTO `comuna` VALUES (7203, 'Pelluhue', 0);
INSERT INTO `comuna` VALUES (7301, 'Curicó', 0);
INSERT INTO `comuna` VALUES (7302, 'Hualañé', 0);
INSERT INTO `comuna` VALUES (7303, 'Licantén', 0);
INSERT INTO `comuna` VALUES (7304, 'Molina', 0);
INSERT INTO `comuna` VALUES (7305, 'Rauco', 0);
INSERT INTO `comuna` VALUES (7306, 'Romeral', 0);
INSERT INTO `comuna` VALUES (7307, 'Sagrada Familia', 0);
INSERT INTO `comuna` VALUES (7308, 'Teno', 0);
INSERT INTO `comuna` VALUES (7309, 'Vichuquén', 0);
INSERT INTO `comuna` VALUES (7401, 'Linares', 0);
INSERT INTO `comuna` VALUES (7402, 'Colbún', 0);
INSERT INTO `comuna` VALUES (7403, 'Longaví', 0);
INSERT INTO `comuna` VALUES (7404, 'Parral', 0);
INSERT INTO `comuna` VALUES (7405, 'Retiro', 0);
INSERT INTO `comuna` VALUES (7406, 'San Javier', 0);
INSERT INTO `comuna` VALUES (7407, 'Villa Alegre', 0);
INSERT INTO `comuna` VALUES (7408, 'Yerbas Buenas', 0);
INSERT INTO `comuna` VALUES (8101, 'Concepción', 0);
INSERT INTO `comuna` VALUES (8102, 'Coronel', 0);
INSERT INTO `comuna` VALUES (8103, 'Chiguayante', 0);
INSERT INTO `comuna` VALUES (8104, 'Florida', 0);
INSERT INTO `comuna` VALUES (8105, 'Hualqui', 0);
INSERT INTO `comuna` VALUES (8106, 'Lota', 0);
INSERT INTO `comuna` VALUES (8107, 'Penco', 0);
INSERT INTO `comuna` VALUES (8108, 'San Pedro de la Paz', 0);
INSERT INTO `comuna` VALUES (8109, 'Santa', 0);
INSERT INTO `comuna` VALUES (8110, 'Talcahuano', 0);
INSERT INTO `comuna` VALUES (8111, 'Tomé', 0);
INSERT INTO `comuna` VALUES (8201, 'Lebu', 0);
INSERT INTO `comuna` VALUES (8202, 'Arauco', 0);
INSERT INTO `comuna` VALUES (8203, 'Cañete', 0);
INSERT INTO `comuna` VALUES (8204, 'Contulmo', 0);
INSERT INTO `comuna` VALUES (8205, 'Curanilahue', 0);
INSERT INTO `comuna` VALUES (8206, 'Los Alamos', 0);
INSERT INTO `comuna` VALUES (8207, 'Tirúa', 0);
INSERT INTO `comuna` VALUES (8301, 'Los Angeles', 0);
INSERT INTO `comuna` VALUES (8302, 'Antuco', 0);
INSERT INTO `comuna` VALUES (8303, 'Cabrero', 0);
INSERT INTO `comuna` VALUES (8304, 'Laja', 0);
INSERT INTO `comuna` VALUES (8305, 'Mulchén', 0);
INSERT INTO `comuna` VALUES (8306, 'Nacimiento', 0);
INSERT INTO `comuna` VALUES (8307, 'Negrete', 0);
INSERT INTO `comuna` VALUES (8308, 'Quilaco', 0);
INSERT INTO `comuna` VALUES (8309, 'Quilleco', 0);
INSERT INTO `comuna` VALUES (8310, 'San Rosendo', 0);
INSERT INTO `comuna` VALUES (8311, 'Santa Bárbara', 0);
INSERT INTO `comuna` VALUES (8312, 'Tucapel', 0);
INSERT INTO `comuna` VALUES (8313, 'Yumbel', 0);
INSERT INTO `comuna` VALUES (8401, 'Chillán', 0);
INSERT INTO `comuna` VALUES (8402, 'Bulnes', 0);
INSERT INTO `comuna` VALUES (8403, 'Cobquecura', 0);
INSERT INTO `comuna` VALUES (8404, 'Coelemu', 0);
INSERT INTO `comuna` VALUES (8405, 'Coihueco', 0);
INSERT INTO `comuna` VALUES (8406, 'Chillán Viejo', 0);
INSERT INTO `comuna` VALUES (8407, 'El Carmen', 0);
INSERT INTO `comuna` VALUES (8408, 'Ninhue', 0);
INSERT INTO `comuna` VALUES (8409, 'Ñiquén', 0);
INSERT INTO `comuna` VALUES (8410, 'Pemuco', 0);
INSERT INTO `comuna` VALUES (8411, 'Pinto', 0);
INSERT INTO `comuna` VALUES (8412, 'Portezuelo', 0);
INSERT INTO `comuna` VALUES (8413, 'Quillón', 0);
INSERT INTO `comuna` VALUES (8414, 'Quirihue', 0);
INSERT INTO `comuna` VALUES (8415, 'Ránquil', 0);
INSERT INTO `comuna` VALUES (8416, 'San Carlos', 0);
INSERT INTO `comuna` VALUES (8417, 'San Fabián', 0);
INSERT INTO `comuna` VALUES (8418, 'San Ignacio', 0);
INSERT INTO `comuna` VALUES (8419, 'San Nicolás', 0);
INSERT INTO `comuna` VALUES (8420, 'Treguaco', 0);
INSERT INTO `comuna` VALUES (8421, 'Yungay', 0);
INSERT INTO `comuna` VALUES (9101, 'Temuco', 0);
INSERT INTO `comuna` VALUES (9102, 'Carahue', 0);
INSERT INTO `comuna` VALUES (9103, 'Cunco', 0);
INSERT INTO `comuna` VALUES (9104, 'Curarrehue', 0);
INSERT INTO `comuna` VALUES (9105, 'Freire', 0);
INSERT INTO `comuna` VALUES (9106, 'Galvarino', 0);
INSERT INTO `comuna` VALUES (9107, 'Gorbea', 0);
INSERT INTO `comuna` VALUES (9108, 'Lautaro', 0);
INSERT INTO `comuna` VALUES (9109, 'Loncoche', 0);
INSERT INTO `comuna` VALUES (9110, 'Melipeuco', 0);
INSERT INTO `comuna` VALUES (9111, 'Nueva Imperial', 0);
INSERT INTO `comuna` VALUES (9112, 'Padre Las Casas', 0);
INSERT INTO `comuna` VALUES (9113, 'Perquenco', 0);
INSERT INTO `comuna` VALUES (9114, 'Pitrufquén', 0);
INSERT INTO `comuna` VALUES (9115, 'Pucón', 0);
INSERT INTO `comuna` VALUES (9116, 'Saavedra', 0);
INSERT INTO `comuna` VALUES (9117, 'Teodoro Schmidt', 0);
INSERT INTO `comuna` VALUES (9118, 'Toltén', 0);
INSERT INTO `comuna` VALUES (9119, 'Vilcún', 0);
INSERT INTO `comuna` VALUES (9120, 'Villarrica', 0);
INSERT INTO `comuna` VALUES (9201, 'Angol', 0);
INSERT INTO `comuna` VALUES (9202, 'Collipulli', 0);
INSERT INTO `comuna` VALUES (9203, 'Curacautín', 0);
INSERT INTO `comuna` VALUES (9204, 'Ercilla', 0);
INSERT INTO `comuna` VALUES (9205, 'Lonquimay', 0);
INSERT INTO `comuna` VALUES (9206, 'Los Sauces', 0);
INSERT INTO `comuna` VALUES (9207, 'Lumaco', 0);
INSERT INTO `comuna` VALUES (9208, 'Purén', 0);
INSERT INTO `comuna` VALUES (9209, 'Renaico', 0);
INSERT INTO `comuna` VALUES (9210, 'Traiguén', 0);
INSERT INTO `comuna` VALUES (9211, 'Victoria', 0);
INSERT INTO `comuna` VALUES (10101, 'Puerto Montt', 0);
INSERT INTO `comuna` VALUES (10102, 'Calbuco', 0);
INSERT INTO `comuna` VALUES (10103, 'Cochamó', 0);
INSERT INTO `comuna` VALUES (10104, 'Fresia', 0);
INSERT INTO `comuna` VALUES (10105, 'Frutillar', 0);
INSERT INTO `comuna` VALUES (10106, 'Los Muermos', 0);
INSERT INTO `comuna` VALUES (10107, 'Llanquihue', 0);
INSERT INTO `comuna` VALUES (10108, 'Maullín', 0);
INSERT INTO `comuna` VALUES (10109, 'Puerto Varas', 0);
INSERT INTO `comuna` VALUES (10201, 'Castro', 0);
INSERT INTO `comuna` VALUES (10202, 'Ancud', 0);
INSERT INTO `comuna` VALUES (10203, 'Chonchi', 0);
INSERT INTO `comuna` VALUES (10204, 'Curaco de Vélez', 0);
INSERT INTO `comuna` VALUES (10205, 'Dalcahue', 0);
INSERT INTO `comuna` VALUES (10206, 'Puqueldón', 0);
INSERT INTO `comuna` VALUES (10207, 'Queilén', 0);
INSERT INTO `comuna` VALUES (10208, 'Quellón', 0);
INSERT INTO `comuna` VALUES (10209, 'Quemchi', 0);
INSERT INTO `comuna` VALUES (10210, 'Quinchao', 0);
INSERT INTO `comuna` VALUES (10301, 'Osorno', 0);
INSERT INTO `comuna` VALUES (10302, 'Puerto Octay', 0);
INSERT INTO `comuna` VALUES (10303, 'Purranque', 0);
INSERT INTO `comuna` VALUES (10304, 'Puyehue', 0);
INSERT INTO `comuna` VALUES (10305, 'Río Negro', 0);
INSERT INTO `comuna` VALUES (10306, 'San Juan de la Costa', 0);
INSERT INTO `comuna` VALUES (10307, 'San Pablo', 0);
INSERT INTO `comuna` VALUES (10401, 'Chaitén', 0);
INSERT INTO `comuna` VALUES (10402, 'Futaleufú', 0);
INSERT INTO `comuna` VALUES (10403, 'Hualaihué', 0);
INSERT INTO `comuna` VALUES (10404, 'Palena', 0);
INSERT INTO `comuna` VALUES (10501, 'Valdivia', 0);
INSERT INTO `comuna` VALUES (10502, 'Corral', 0);
INSERT INTO `comuna` VALUES (10503, 'Futrono', 0);
INSERT INTO `comuna` VALUES (10504, 'La Unión', 0);
INSERT INTO `comuna` VALUES (10505, 'Lago Ranco', 0);
INSERT INTO `comuna` VALUES (10506, 'Lanco', 0);
INSERT INTO `comuna` VALUES (10507, 'Los Lagos', 0);
INSERT INTO `comuna` VALUES (10508, 'Máfil', 0);
INSERT INTO `comuna` VALUES (10509, 'Mariquina', 0);
INSERT INTO `comuna` VALUES (10510, 'Paillaco', 0);
INSERT INTO `comuna` VALUES (10511, 'Panguipulli', 0);
INSERT INTO `comuna` VALUES (10512, 'Río Bueno', 0);
INSERT INTO `comuna` VALUES (11101, 'Coihaique', 0);
INSERT INTO `comuna` VALUES (11102, 'Lago Verde', 0);
INSERT INTO `comuna` VALUES (11201, 'Aisén', 0);
INSERT INTO `comuna` VALUES (11202, 'Cisnes', 0);
INSERT INTO `comuna` VALUES (11203, 'Guaitecas', 0);
INSERT INTO `comuna` VALUES (11301, 'Cochrane', 0);
INSERT INTO `comuna` VALUES (11302, 'O´Higgins', 0);
INSERT INTO `comuna` VALUES (11303, 'Tortel', 0);
INSERT INTO `comuna` VALUES (11401, 'Chile Chico', 0);
INSERT INTO `comuna` VALUES (11402, 'Río Ibáñez', 0);
INSERT INTO `comuna` VALUES (12101, 'Punta Arenas', 0);
INSERT INTO `comuna` VALUES (12102, 'Laguna Blanca', 0);
INSERT INTO `comuna` VALUES (12103, 'Río Verde', 0);
INSERT INTO `comuna` VALUES (12104, 'San Gregorio', 0);
INSERT INTO `comuna` VALUES (12201, 'Navarino', 0);
INSERT INTO `comuna` VALUES (12202, 'Antártica', 0);
INSERT INTO `comuna` VALUES (12301, 'Porvenir', 0);
INSERT INTO `comuna` VALUES (12302, 'Primavera', 0);
INSERT INTO `comuna` VALUES (12303, 'Timaukel', 0);
INSERT INTO `comuna` VALUES (12401, 'Natales', 0);
INSERT INTO `comuna` VALUES (12402, 'Torres del Paine', 0);
INSERT INTO `comuna` VALUES (13101, 'Santiago', 0);
INSERT INTO `comuna` VALUES (13102, 'Cerrillos', 0);
INSERT INTO `comuna` VALUES (13103, 'Cerro Navia', 0);
INSERT INTO `comuna` VALUES (13104, 'Conchalí', 0);
INSERT INTO `comuna` VALUES (13105, 'El Bosque', 0);
INSERT INTO `comuna` VALUES (13106, 'Estación Central -', 0);
INSERT INTO `comuna` VALUES (13107, 'Huechuraba', 0);
INSERT INTO `comuna` VALUES (13108, 'Independencia', 0);
INSERT INTO `comuna` VALUES (13109, 'La Cisterna', 0);
INSERT INTO `comuna` VALUES (13110, 'La Florida', 0);
INSERT INTO `comuna` VALUES (13111, 'La Granja', 0);
INSERT INTO `comuna` VALUES (13112, 'La Pintana', 0);
INSERT INTO `comuna` VALUES (13113, 'La Reina', 0);
INSERT INTO `comuna` VALUES (13114, 'Las Condes', 0);
INSERT INTO `comuna` VALUES (13115, 'Lo Barnechea', 0);
INSERT INTO `comuna` VALUES (13116, 'Lo Espejo', 0);
INSERT INTO `comuna` VALUES (13117, 'Lo Prado', 0);
INSERT INTO `comuna` VALUES (13118, 'Macul', 0);
INSERT INTO `comuna` VALUES (13119, 'Maipú', 0);
INSERT INTO `comuna` VALUES (13120, 'Ñuñoa', 0);
INSERT INTO `comuna` VALUES (13121, 'Pedro Aguirre', 0);
INSERT INTO `comuna` VALUES (13122, 'Peñalolén -', 0);
INSERT INTO `comuna` VALUES (13123, 'Providencia', 0);
INSERT INTO `comuna` VALUES (13124, 'Pudahuel', 0);
INSERT INTO `comuna` VALUES (13125, 'Quilicura', 0);
INSERT INTO `comuna` VALUES (13126, 'Quinta', 0);
INSERT INTO `comuna` VALUES (13127, 'Recoleta', 0);
INSERT INTO `comuna` VALUES (13128, 'Renca', 0);
INSERT INTO `comuna` VALUES (13129, 'San Joaquín', 0);
INSERT INTO `comuna` VALUES (13130, 'San', 0);
INSERT INTO `comuna` VALUES (13131, 'San Ramón', 0);
INSERT INTO `comuna` VALUES (13132, 'Vitacura', 0);
INSERT INTO `comuna` VALUES (13201, 'Puente Alto', 0);
INSERT INTO `comuna` VALUES (13202, 'Pirque', 0);
INSERT INTO `comuna` VALUES (13203, 'San José de Maipo', 0);
INSERT INTO `comuna` VALUES (13301, 'Colina', 0);
INSERT INTO `comuna` VALUES (13302, 'Lampa', 0);
INSERT INTO `comuna` VALUES (13303, 'Tiltil', 0);
INSERT INTO `comuna` VALUES (13401, 'San Bernardo', 0);
INSERT INTO `comuna` VALUES (13402, 'Buin', 0);
INSERT INTO `comuna` VALUES (13403, 'Calera de Tango', 0);
INSERT INTO `comuna` VALUES (13404, 'Paine', 0);
INSERT INTO `comuna` VALUES (13501, 'Melipilla', 0);
INSERT INTO `comuna` VALUES (13502, 'Alhué', 0);
INSERT INTO `comuna` VALUES (13503, 'Curacaví', 0);
INSERT INTO `comuna` VALUES (13504, 'María Pinto', 0);
INSERT INTO `comuna` VALUES (13505, 'San Pedro', 0);
INSERT INTO `comuna` VALUES (13601, 'Talagante', 0);
INSERT INTO `comuna` VALUES (13602, 'El Monte', 0);
INSERT INTO `comuna` VALUES (13603, 'Isla de Maipo', 0);
INSERT INTO `comuna` VALUES (13604, 'Padre Hurtado', 0);
INSERT INTO `comuna` VALUES (13605, 'Peñaflor', 0);

-- --------------------------------------------------------

-- 
-- Estructura de tabla para la tabla `menu`
-- 

CREATE TABLE `menu` (
  `codigo` varchar(10) NOT NULL default '',
  `glosa` varchar(50) NOT NULL default '',
  `url` varchar(100) default NULL,
  `tipo` int(11) NOT NULL default '0',
  `orden` int(11) NOT NULL default '0',
  `codigo_padre` varchar(10) NOT NULL default '',
  PRIMARY KEY  (`codigo`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Volcar la base de datos para la tabla `menu`
-- 

INSERT INTO `menu` VALUES ('ADMIN', 'Administración General', NULL, 1, 0, '');
INSERT INTO `menu` VALUES ('USER', 'Usuarios', '/app/usuarios/usuario_paso1.php', 0, 1, 'ADMIN');
INSERT INTO `menu` VALUES ('PER', 'Datos personales', NULL, 1, 10000, '');
INSERT INTO `menu` VALUES ('PER_EDIT', 'Editar Datos Personales', '/app/usuarios/datos_personales.php', 0, 10000, 'PER');
INSERT INTO `menu` VALUES ('LOGOUT', 'Salir', '/fw/usuarios/logout.php', 0, 99999, 'PER');

-- --------------------------------------------------------

-- 
-- Estructura de tabla para la tabla `menu_permiso`
-- 

CREATE TABLE `menu_permiso` (
  `codigo_permiso` varchar(12) NOT NULL default '',
  `codigo_menu` varchar(10) NOT NULL default '',
  PRIMARY KEY  (`codigo_permiso`,`codigo_menu`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Volcar la base de datos para la tabla `menu_permiso`
-- 

INSERT INTO `menu_permiso` VALUES ('ADM', 'ADMIN');
INSERT INTO `menu_permiso` VALUES ('ADM', 'PER');
INSERT INTO `menu_permiso` VALUES ('ALL', 'LOGOUT');
INSERT INTO `menu_permiso` VALUES ('ALL', 'PER_EDIT');
INSERT INTO `menu_permiso` VALUES ('ALL', 'USER');

-- --------------------------------------------------------

-- 
-- Estructura de tabla para la tabla `prm_comuna`
-- 

CREATE TABLE `prm_comuna` (
  `id_comuna` int(11) NOT NULL default '0',
  `glosa_comuna` char(64) NOT NULL default '',
  `region` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id_comuna`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Volcar la base de datos para la tabla `prm_comuna`
-- 


-- --------------------------------------------------------

-- 
-- Estructura de tabla para la tabla `prm_permisos`
-- 

CREATE TABLE `prm_permisos` (
  `codigo_permiso` char(12) NOT NULL default '',
  `glosa` char(100) default NULL,
  PRIMARY KEY  (`codigo_permiso`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Volcar la base de datos para la tabla `prm_permisos`
-- 

INSERT INTO `prm_permisos` VALUES ('ADM', 'Administración General');
INSERT INTO `prm_permisos` VALUES ('VEN', 'Ventas');
INSERT INTO `prm_permisos` VALUES ('GER', 'Gerencia');
INSERT INTO `prm_permisos` VALUES ('ALL', 'Todos Los usuarios');

-- --------------------------------------------------------

-- 
-- Estructura de tabla para la tabla `usuario`
-- 

CREATE TABLE `usuario` (
  `rut` int(11) NOT NULL default '0',
  `dv_rut` char(1) NOT NULL default '',
  `nombre` varchar(30) NOT NULL default '',
  `apellido1` varchar(30) NOT NULL default '',
  `apellido2` varchar(30) NOT NULL default '',
  `dir_calle` varchar(30) NOT NULL default '',
  `dir_numero` int(11) NOT NULL default '0',
  `dir_depto` varchar(5) NOT NULL default '',
  `dir_comuna` int(11) NOT NULL default '0',
  `telefono1` varchar(10) NOT NULL default '',
  `telefono2` varchar(10) NOT NULL default '',
  `email` varchar(30) NOT NULL default '',
  `password` varchar(128) NOT NULL default '',
  `permisos_admin` tinyint(4) NOT NULL default '0',
  `ultimo_ingreso` datetime NOT NULL default '0000-00-00 00:00:00',
  `activo` tinyint(4) NOT NULL default '1',
  `fecha_creacion` datetime NOT NULL default '0000-00-00 00:00:00',
  `fecha_edicion` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`rut`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Volcar la base de datos para la tabla `usuario`
-- 

INSERT INTO `usuario` VALUES (13905852, '6', 'Ignacio', 'Canals', 'Cavagnaro', 'Matto Grosso', 1459, '', 13114, '20202749', '', '', '827ccb0eea8a706c4c34a16891f84e7b', 0, '2006-02-23 11:07:06', 1, '2006-02-15 00:00:00', '2006-02-22 18:07:37');
INSERT INTO `usuario` VALUES (13971122, 'K', 'Juan Pablo', 'Cuevas', 'Dauvin', 'Las Lavandulas', 1234, '', 13114, '2153371', '', '', 'd8b40be17188301265e095cc32a902ee', 0, '0000-00-00 00:00:00', 1, '2006-02-21 15:24:00', '2006-02-21 15:34:24');
INSERT INTO `usuario` VALUES (13883339, '9', 'Andrés', 'Barriga', 'Fherman', 'Las Tortolas', 1212, '', 13114, '2122971', '', '', '68652365d2b96b06e158336b41f848e6', 0, '0000-00-00 00:00:00', 1, '2006-02-21 16:58:49', '2006-02-21 16:59:59');

-- --------------------------------------------------------

-- 
-- Estructura de tabla para la tabla `usuario_permiso`
-- 

CREATE TABLE `usuario_permiso` (
  `rut` int(11) NOT NULL default '0',
  `codigo_permiso` char(12) NOT NULL default '',
  PRIMARY KEY  (`rut`,`codigo_permiso`),
  KEY `Ref55` (`rut`),
  KEY `Ref66` (`codigo_permiso`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Volcar la base de datos para la tabla `usuario_permiso`
-- 

INSERT INTO `usuario_permiso` VALUES (-1, 'ALL');
INSERT INTO `usuario_permiso` VALUES (13883339, 'ADM');
INSERT INTO `usuario_permiso` VALUES (13905852, 'ADM');
INSERT INTO `usuario_permiso` VALUES (13971122, 'ADM');
INSERT INTO `usuario_permiso` VALUES (13971122, 'GER');



--- AGREGADO CAMBPO "monto_cobrado_monedabase" DESPUES DE "monto_cobrado"
--- 
ALTER TABLE `trabajo` ADD `monto_cobrado_monedabase` DOUBLE NOT NULL AFTER `monto_cobrado` ;



--- AGREGANDO CAMPOS A TABLA COBRO_ASUNTO
---
ALTER TABLE `cobro_asunto` ADD `id_moneda` INT NOT NULL ,
ADD `monto_cobrado_monedabase` DOUBLE NOT NULL ;


--- AGREGANDO CAMPOS A LA TABLA COBROS
---
ALTER TABLE `cobro` ADD `id_moneda` INT NOT NULL ,
ADD `monto_cobrado_monedabase` DOUBLE NOT NULL ;  

--- 
ALTER TABLE `cobro_asunto` ADD `id_cobro_asunto` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST ;
ALTER TABLE `cobro_asunto` ADD UNIQUE `cobro_asunto` ( `id_cobro` , `codigo_asunto` ) ;


--- AGREGA CAMPOS A TABLA CLIENTE
ALTER TABLE `cliente` ADD `monto` DOUBLE NOT NULL AFTER `id_cliente` ,
ADD `forma_cobro` ENUM( 'TASA', 'SUMA', 'RETAINER', 'FLAT FEE', 'HONORARIOS' ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL AFTER `monto` ;


--- Agrega Campo "cobro_independiente" a "asunto"
ALTER TABLE `asunto` ADD `cobro_independiente` VARCHAR( 2 ) NOT NULL DEFAULT 'NO';


--- Agrega campo monto_gastos para almacenar el monto total de los gatos asociado al cobro
ALTER TABLE `cobro` ADD `monto_gastos` DOUBLE NOT NULL ;



---- 20 junio 
---- TABLA 'cobro' cambios
ALTER TABLE `cobro` CHANGE `monto_gastos` `monto_gastos_monedabase` DOUBLE NOT NULL DEFAULT '0'
ALTER TABLE `cobro` ADD `costo_hh` DOUBLE NOT NULL ,
ADD `costo_hh_monedabase` DOUBLE NOT NULL ;

--- Edicion 'cobro' campos en NULL
ALTER TABLE `cobro` CHANGE `costo_hh_monedabase` `costo_hh_monedabase` DOUBLE NULL 
ALTER TABLE `cobro` CHANGE `costo_hh` `costo_hh` DOUBLE NULL 
ALTER TABLE `cobro` CHANGE `monto_gastos_monedabase` `monto_gastos_monedabase` DOUBLE NULL 
ALTER TABLE `cobro` CHANGE `monto_cobrado_monedabase` `monto_cobrado_monedabase` DOUBLE NULL
ALTER TABLE `cobro` CHANGE `id_moneda` `id_moneda` INT( 11 ) NOT NULL DEFAULT '1'
ALTER TABLE `cobro_asunto` CHANGE `monto_cobrado_monedabase` `monto_cobrado_monedabase` DOUBLE NULL 

--- Agrega campos 'cobro_asunto' 
ALTER TABLE `cobro_asunto` ADD `costo_hh` DOUBLE NULL ,
ADD `costo_hh_monedabase` DOUBLE NULL ;

--- Agrega tabla 'trabajo'
ALTER TABLE `trabajo` ADD `costo_hh` DOUBLE NULL AFTER `monto_cobrado_monedabase` ,
ADD `costo_hh_monedabase` DOUBLE NULL AFTER `costo_hh` ;


--- Agregamos campo 'id_moneda_costo' a tabla 'usuarios' 
ALTER TABLE `usuario` ADD `id_moneda_costo` INT NULL DEFAULT '1';

--- Cambia ENUM para Forma_Cobro
ALTER TABLE `asunto` CHANGE `forma_cobro` `forma_cobro` ENUM( 'TASA', 'RETAINER', 'FLAT FEE' ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'TASA'

-- Mejora en cliente
ALTER TABLE `cliente` CHANGE `monto` `monto` DOUBLE NULL; 
UPDATE asunto SET cobro_independiente = 'SI';

-- Monto_Cobrado_MonedaBas
ALTER TABLE `cobro` ADD `monto_gastos_monedabase` DOUBLE NULL DEFAULT NULL AFTER `monto_cobrado_monedabase` ;
ALTER TABLE `cobro` ADD `costo_hh_monedabase` DOUBLE NULL DEFAULT NULL ;
ALTER TABLE `cobro` ADD `costo_hh` DOUBLE NULL DEFAULT NULL ;


-- Cambia tipo dato a RUT en Cliente
ALTER TABLE `cliente` CHANGE `rut` `rut` VARCHAR( 50 ) NULL DEFAULT '0' 

-- Cambia tipo dato dir_calle a utilizar como direcci򬬊ALTER TABLE `cliente` CHANGE `dir_calle` `dir_calle` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL  

-- agrega campo a cobro
ALTER TABLE `cobro` ADD `opc_moneda_total` INT NULL COMMENT 'Moneda total de impresión del DOC';

-- agrega campo tipo cambio de moneda total
ALTER TABLE `cobro` ADD `opc_moneda_total_tipo_cambio` DOUBLE NOT NULL DEFAULT '0' COMMENT 'Tipo de cambio de la moneda presentada en la impresión del DOC';

-- cambia tipo de datos en cliente
ALTER TABLE `cliente` CHANGE `dir_calle` `dir_calle` VARCHAR( 150 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL ,
CHANGE `fono_contacto` `fono_contacto` INT( 20 ) NULL DEFAULT NULL 

-- cambia largo rut cliente
ALTER TABLE `cliente` CHANGE `rut` `rut` VARCHAR( 20 ) NULL DEFAULT '0'

-- SQL concat digito para rut quedando como dato único
UPDATE cliente SET rut = CONCAT(rut,'-',dv) WHERE rut != '0' OR rut != ''

-- SQL crea tabla cobro_moneda, guarda cambios de moneda actual del cobro
CREATE TABLE `cobro_moneda` (
`id_cobro` INT( 11 ) NOT NULL DEFAULT '0',
`id_moneda` INT( 11 ) NOT NULL DEFAULT '0',
`tipo_cambio` DOUBLE NOT NULL DEFAULT '0'
) ENGINE = innodb;

-- tabla de cartas
CREATE TABLE `carta` (
`id_carta` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`descripcion` VARCHAR( 55 ) NULL ,
`formato` TEXT NULL
) ENGINE = innodb;

ALTER TABLE `carta` ADD `formato_css` TEXT NULL ;


-- Cambia largo de dato fono contacto
ALTER TABLE `cliente` CHANGE `fono_contacto` `fono_contacto` VARCHAR( 200 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL 


-- nuevo menu reporte
INSERT INTO `menu` ( `codigo` , `glosa` , `url` , `descripcion` , `foto_url` , `tipo` , `orden` , `codigo_padre` )
VALUES (
'RAP', 'Periódico', '/app/interfaces/resumen_actividades.php', '', '', '0', '70', 'REP'
);

INSERT INTO `menu_permiso` ( `codigo_permiso` , `codigo_menu` )
VALUES (
'REP', 'RAP'
);

-- Nuevas funcionaidades en cobranza
CREATE TABLE `tarifa` (
`id_tarifa` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`glosa_tarifa` VARCHAR( 150 ) NULL 
) ENGINE = innodb;

ALTER TABLE `usuario_tarifa` ADD `id_tarifa` INT NOT NULL ;

ALTER TABLE `usuario_tarifa` ADD INDEX ( `id_tarifa` ) ;

ALTER TABLE `usuario_tarifa` ADD INDEX ( `id_tarifa` , `id_usuario` , `id_moneda` ) ;


-- agrega campos a contrato tabla
ALTER TABLE `contrato` ADD `rut` VARCHAR( 20 ) NULL ,
ADD `factura_razon_social` VARCHAR( 200 ) NULL ,
ADD `factura_giro` VARCHAR( 200 ) NULL ,
ADD `factura_direccion` MEDIUMTEXT NULL ,
ADD `factura_telefono` VARCHAR( 100 ) NULL ,
ADD `id_tarifa` INT NULL ;

ALTER TABLE `contrato` ADD `cod_factura_telefono` VARCHAR( 10 ) NULL AFTER `factura_telefono` ;

-- campo en tabla cliente
ALTER TABLE `cliente` ADD `id_contrato` INT NULL ;

-- agrega campo a asunto
ALTER TABLE `asunto` ADD `id_contrato_indep` INT NULL ;

ALTER TABLE `asunto` DROP INDEX `codigo_contrato` ,
ADD INDEX `id_contrato` ( `id_contrato` ) ;

ALTER TABLE `contrato` CHANGE `glosa_contrato` `glosa_contrato` MEDIUMTEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL 

-- agrega campo dato en tarifa
ALTER TABLE `tarifa` ADD `fecha_creacion` DATE NOT NULL DEFAULT '0000-00-00';
-- agrega campo dato en tarifa
ALTER TABLE `tarifa` ADD `fecha_modificacion` DATE NOT NULL DEFAULT '0000-00-00';

ALTER TABLE `usuario_tarifa` DROP INDEX `id_usuario_2` ;

ALTER TABLE `usuario_tarifa` DROP INDEX `id_tarifa_2` ,
ADD UNIQUE `id_tarifa_2` ( `id_tarifa` , `id_usuario` , `id_moneda` ) ;

-- agrega campo a tabla tarifa
ALTER TABLE `tarifa` ADD `tarifa_defecto` TINYINT NOT NULL DEFAULT '0';




-- Cambios despues UPDATE CONTRATO
ALTER TABLE `contrato` ADD `fecha_inicio_cap` DATE NOT NULL DEFAULT '0000-00-00';

-- Agrega menu
INSERT INTO `menu` ( `codigo` , `glosa` , `url` , `descripcion` , `foto_url` , `tipo` , `orden` , `codigo_padre` )
VALUES (
'TARIFA', 'Tarifas', '/app/interfaces/agregar_tarifa.php?id_tarifa_edicion=1', '', '', '0', '54', 'COBRANZA'
);
INSERT INTO `menu_permiso` ( `codigo_permiso` , `codigo_menu` )
VALUES (
'COB', 'TARIFA'
);


-- Indice para update tabla cobro-historial y llave foránea
ALTER TABLE `cobro_historial` ADD INDEX  (`id_cobro`);
ALTER TABLE `cobro_historial` ADD CONSTRAINT `cobro_historial_fk` FOREIGN KEY (`id_cobro`) REFERENCES `cobro` (`id_cobro`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Incluir en cierre
ALTER TABLE `contrato` ADD `incluir_en_cierre` TINYINT NOT NULL DEFAULT '1';

-- agrega campos a tabla contrato
ALTER TABLE `contrato` ADD `opc_ver_modalidad` TINYINT NOT NULL DEFAULT '1',
ADD `opc_ver_profesional` TINYINT NOT NULL DEFAULT '1',
ADD `opc_ver_gastos` TINYINT NOT NULL DEFAULT '1',
ADD `opc_ver_descuento` TINYINT NOT NULL DEFAULT '1',
ADD `opc_ver_numpag` TINYINT NOT NULL DEFAULT '1',
ADD `opc_ver_carta` TINYINT NOT NULL DEFAULT '1',
ADD `opc_papel` VARCHAR( 16 ) NOT NULL DEFAULT 'LETTER',
ADD `opc_moneda_total` TINYINT NOT NULL DEFAULT '1';


-- Update menu
UPDATE `menu` SET `glosa` = 'Generación de Cobros',
`url` = '/app/interfaces/genera_cobros.php',
`codigo_padre` = 'COBRANZA' WHERE CONVERT( `codigo` USING utf8 ) = 'COBROS' LIMIT 1 ;