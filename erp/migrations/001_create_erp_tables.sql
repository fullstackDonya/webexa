-- ERP core tables
CREATE TABLE IF NOT EXISTS erp_companies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  siret VARCHAR(20) NULL,
  address_line1 VARCHAR(255) NULL,
  address_line2 VARCHAR(255) NULL,
  postal_code VARCHAR(20) NULL,
  city VARCHAR(120) NULL,
  country VARCHAR(120) DEFAULT 'France',
  phone VARCHAR(50) NULL,
  email VARCHAR(180) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_employees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NULL,
  first_name VARCHAR(120) NOT NULL,
  last_name VARCHAR(120) NOT NULL,
  email VARCHAR(180) NULL,
  hire_date DATE NULL,
  base_salary DECIMAL(10,2) DEFAULT 0,
  job_title VARCHAR(180) NULL,
  department VARCHAR(180) NULL,
  contract_type ENUM('CDI','CDD','Freelance','Stage','Alternance') DEFAULT 'CDI',
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_emp_company FOREIGN KEY (company_id) REFERENCES erp_companies(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_payrolls (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  period CHAR(7) NOT NULL COMMENT 'YYYY-MM',
  gross_salary DECIMAL(10,2) NOT NULL,
  bonus DECIMAL(10,2) NOT NULL DEFAULT 0,
  overtime DECIMAL(10,2) NOT NULL DEFAULT 0,
  deductions DECIMAL(10,2) NOT NULL DEFAULT 0,
  employee_contrib DECIMAL(10,2) NOT NULL,
  employer_contrib DECIMAL(10,2) NOT NULL,
  net_pay DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_emp_period (employee_id, period),
  CONSTRAINT fk_payroll_employee FOREIGN KEY (employee_id) REFERENCES erp_employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


ALTER TABLE erp_companies
  ADD COLUMN IF NOT EXISTS customer_id INT NULL AFTER id;

ALTER TABLE erp_employees
  ADD COLUMN IF NOT EXISTS customer_id INT NULL AFTER company_id;

ALTER TABLE erp_payrolls
  ADD COLUMN IF NOT EXISTS customer_id INT NULL AFTER employee_id;

-- Indexer les colonnes pour les recherches / jointures (créera l'index seulement si non présent)
-- Note : MySQL ne supporte pas CREATE INDEX IF NOT EXISTS dans toutes les versions,
-- ces instructions peuvent générer une erreur si l'index existe déjà. Supprimer le commentaire
-- et exécuter manuellement si nécessaire.
ALTER TABLE erp_companies ADD INDEX idx_erp_companies_customer_id (customer_id);
ALTER TABLE erp_employees  ADD INDEX idx_erp_employees_customer_id  (customer_id);
ALTER TABLE erp_payrolls   ADD INDEX idx_erp_payrolls_customer_id   (customer_id);



DROP TABLE IF EXISTS erp_shifts;
CREATE TABLE IF NOT EXISTS erp_shifts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME NOT NULL,
  role VARCHAR(150) DEFAULT NULL,
  notes TEXT,
  company_id INT DEFAULT NULL,
  customer_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_shift_employee (employee_id),
  INDEX idx_shift_customer (customer_id),
  INDEX idx_shift_company (company_id),
  CONSTRAINT fk_shifts_company_uniq FOREIGN KEY (company_id) REFERENCES erp_companies(id) ON DELETE SET NULL,
  CONSTRAINT fk_shifts_employee_uniq FOREIGN KEY (employee_id) REFERENCES erp_employees(id) ON DELETE CASCADE,
  CONSTRAINT fk_shifts_customer_uniq FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS erp_stock (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    price DECIMAL(10, 2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_inventory (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    location VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE old_sales_table DROP FOREIGN KEY fk_sales_product;
ALTER TABLE erp_sales_backup DROP FOREIGN KEY fk_sales_customer;
DROP TABLE IF EXISTS erp_sales;

CREATE TABLE IF NOT EXISTS erp_sales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    sale_price DECIMAL(10,2) NOT NULL,
    sale_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    customer_id INT NULL,
    INDEX idx_sales_product (product_id),
    INDEX idx_sales_customer (customer_id),
    CONSTRAINT fk_sales_product_uniq FOREIGN KEY (product_id) REFERENCES erp_stock(id) ON DELETE CASCADE,
    CONSTRAINT fk_sales_customer_uniq FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE erp_sales
ADD COLUMN employee_id INT NULL AFTER product_id,
ADD INDEX idx_sales_employee (employee_id),
ADD CONSTRAINT fk_sales_employee_uniq FOREIGN KEY (employee_id)
    REFERENCES erp_employees(id)
    ON DELETE SET NULL;


ALTER TABLE erp_stock
ADD COLUMN customer_id INT NULL AFTER id;

ALTER TABLE erp_stock
ADD CONSTRAINT fk_stock_customer
FOREIGN KEY (customer_id) REFERENCES customers(id)
ON DELETE SET NULL ON UPDATE CASCADE;


ALTER TABLE erp_inventory
ADD COLUMN customer_id INT NULL AFTER id;
ALTER TABLE erp_inventory
ADD CONSTRAINT fk_inventory_customer
FOREIGN KEY (customer_id) REFERENCES customers(id)
ON DELETE SET NULL ON UPDATE CASCADE;


ALTER TABLE erp_inventory
ADD COLUMN description VARCHAR(500) NULL;
