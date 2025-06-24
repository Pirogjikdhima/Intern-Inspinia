<?php
const DB_SERVER = 'localhost';
const DB_USERNAME = 'root';
const DB_PASSWORD = '';
const DB_NAME = 'internship';
const DB_PORT = 3306;
const PasswordRegex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&.*])[A-Za-z\d!@#$%^&.*]{8,}$/";
const NumberRegex = "/^0?\d{9,}$/";
const UsernameRegex = "/^[a-zA-Z0-9]{3,}$/";
const NameRegex = "/^[a-zA-Z]{2,}$/";


const OretQeKaPunuarQuery = 'SUM(
    CASE
        WHEN ora_hyrje < ora_dalje THEN
            TIMESTAMPDIFF(SECOND, ora_hyrje, ora_dalje)
        WHEN ora_hyrje = ora_dalje THEN
            0
        ELSE
            TIMESTAMPDIFF(SECOND, ora_dalje, ora_hyrje)
    END
) / 3600';

const OretQeKaPunuarNeOrarPuneQuery = 'SUM(
    CASE
        WHEN ora_hyrje < ora_dalje AND
            (ora_hyrje BETWEEN \'09:00:00\' AND \'17:59:59\') AND
            (ora_dalje BETWEEN \'09:00:00\' AND \'18:00:00\') THEN
            TIMESTAMPDIFF(SECOND, ora_hyrje, ora_dalje)
        ELSE
            0
    END
) / 3600';

const OreQePunuarJashteOraritQuery = 'SUM(
    CASE
        WHEN ora_hyrje < ora_dalje THEN
            TIMESTAMPDIFF(SECOND, ora_hyrje, ora_dalje)
        WHEN ora_hyrje = ora_dalje THEN
            0
        ELSE
            TIMESTAMPDIFF(SECOND, ora_dalje, ora_hyrje)
    END
) / 3600 - SUM(
    CASE
        WHEN ora_hyrje < ora_dalje AND
            (ora_hyrje BETWEEN \'09:00:00\' AND \'17:59:59\') AND
            (ora_dalje BETWEEN \'09:00:00\' AND \'18:00:00\') THEN
            TIMESTAMPDIFF(SECOND, ora_hyrje, ora_dalje)
        ELSE
            0
    END
) / 3600';

const OretQeNukKaPunuarNeOrarPuneQuery = '9 * COUNT(DISTINCT data_hyrje) - 
SUM(
    CASE
        WHEN ora_hyrje < ora_dalje AND
            (ora_hyrje BETWEEN \'09:00:00\' AND \'17:59:59\') AND
            (ora_dalje BETWEEN \'09:00:00\' AND \'18:00:00\') THEN
            TIMESTAMPDIFF(SECOND, ora_hyrje, ora_dalje)
        ELSE
            0
    END
) / 3600';

