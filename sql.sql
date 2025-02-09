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
    VALUES (NEW.id, NEW.full_name, NEW.phone);
END $$

DELIMITER ;

DROP TRIGGER IF EXISTS after_employee_insert;

