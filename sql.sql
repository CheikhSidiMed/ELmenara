ALTER TABLE `offer_transactions` ADD `type` VARCHAR(20) NOT NULL AFTER `transaction_date`;



	23	start	varchar(100)	utf8mb4_general_ci		Oui	NULL			Modifier Modifier	Supprimer Supprimer	
	24	is_active	int(11)			Non	0			Modifier Modifier	Supprimer Supprimer	
	25	elmoutoune	varchar(255)	utf8mb4_general_ci		Oui	NULL			Modifier Modifier	Supprimer Supprimer	
	26	balance	decimal(10,0)			Oui	0			Modifier Modifier	Supprimer Supprimer	
	27	date_desectivation	varchar(100)	utf8mb4_general_ci		Oui	NULL			Modifier Modifier	Supprimer Supprimer	
	28	suspension_reason	varchar(150)	utf8mb4_general_ci		Oui	NULL			Modifier Modifier	Supprimer Supprimer	



CREATE TABLE `ab_mahraa` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `num_ab_ac` INT(11) NOT NULL,
  `num_ab_no` INT(2) NOT NULL,
  `du` VARCHAR(20) NOT NULL,
  `au` VARCHAR(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_abmahraa_student` (`student_id`),
  CONSTRAINT `fk_abmahraa_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;





-------
ALTER TABLE `processed_salaries` CHANGE `year` `year` VARCHAR(20) NOT NULL;
ALTER TABLE `students` ADD `rewaya` VARCHAR(100) NULL AFTER `level_id`, ADD `days` VARCHAR(200) NULL AFTER `rewaya`, ADD `tdate` VARCHAR(100) NULL AFTER `days`;
ALTER TABLE `students` CHANGE `part_count` `part_count` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `students` ADD `start` VARCHAR(100) NULL AFTER `tdate`;
ALTER TABLE `students` ADD `is_active` INT NOT NULL DEFAULT '0' AFTER `start`;

----
CREATE TABLE user_branch (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    class_id INT DEFAULT 0,
    branch_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id) ON DELETE CASCADE
);

ALTER TABLE users 
ADD COLUMN employee_id INT,
ADD CONSTRAINT fk_employee 
FOREIGN KEY (employee_id) REFERENCES employees(id) 
ON DELETE CASCADE;


DELIMITER $$

CREATE TRIGGER after_employee_insert
AFTER INSERT ON employees
FOR EACH ROW
BEGIN
    INSERT INTO users (employee_id, username, password)
    VALUES (NEW.id, NEW.phone, NEW.phone);
END $$

DELIMITER ;

DROP TRIGGER IF EXISTS after_employee_insert;


-- Programmer l'exécution automatique avec le Planificateur de tâches
-- Ouvre le Planificateur de tâches (tape Planificateur de tâches dans la barre de recherche Windows).
-- Clique sur Créer une tâche de base.
-- Nom de la tâche : "Backup MySQL XAMPP".
-- Déclencheur :
-- Sélectionne Quotidien.
-- Mets l’heure à 00:00.
-- Action :
-- Choisis Démarrer un programme.
-- Dans Programme/script, clique sur Parcourir et sélectionne ton fichier backup.bat.
-- Valide et Enregistre.



