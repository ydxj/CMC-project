CREATE DATABASE EmploiDuTemps;
USE EmploiDuTemps;

-- ETABLISSEMENTS TABLE (Multi-tenant support)
CREATE TABLE etablissements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_etablissement VARCHAR(150) NOT NULL UNIQUE,
    type_etablissement ENUM('CMC', 'EFP') NOT NULL,
    city_id INT NOT NULL,
    id_directeur INT UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (city_id) REFERENCES ville(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- USERS TABLE (Updated for multi-establishment)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    matricule VARCHAR(50) UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('DOSI', 'DIRECTEUR_CMC', 'DIRECTEUR_EFP', 'CHEF_POLE', 'FORMATEUR') NOT NULL,
    id_etablissement INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_etablissement) REFERENCES etablissements(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    INDEX idx_role (role),
    INDEX idx_etablissement (id_etablissement)
) ENGINE=InnoDB;

-- Add foreign key constraint to etablissements after users table is created
ALTER TABLE etablissements
ADD CONSTRAINT fk_etablissement_directeur
FOREIGN KEY (id_directeur) REFERENCES users(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE;

-- FORMATEUR
CREATE TABLE formateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL UNIQUE,
    id_etablissement INT NOT NULL,
    type_formateur ENUM('FP','FV'),
    MHS INT,
    NBR_semaine INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_etablissement) REFERENCES etablissements(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    INDEX idx_etablissement (id_etablissement)
) ENGINE=InnoDB;

-- POLES (Only for CMC establishments)
CREATE TABLE poles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_etablissement INT NOT NULL,
    nom_pole VARCHAR(100) NOT NULL,
    id_chef INT UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (id_etablissement, nom_pole),
    FOREIGN KEY (id_etablissement) REFERENCES etablissements(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_chef) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    INDEX idx_etablissement (id_etablissement)
) ENGINE=InnoDB;


-- formateur_pole (Many-to-Many between formateur and Poles)
CREATE TABLE formateur_pole (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_formateur INT NOT NULL,
    id_pole INT NOT NULL,
    FOREIGN KEY (id_formateur) REFERENCES formateurs(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_pole) REFERENCES poles(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- SALLES (Can belong to Pole for CMC or directly to Etablissement for CFP)
CREATE TABLE salles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_etablissement INT NOT NULL,
    nom_salle VARCHAR(100) NOT NULL,
    type_salle ENUM('SALLE', 'ATELIER') NOT NULL,
    id_pole INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (id_etablissement, nom_salle),
    FOREIGN KEY (id_etablissement) REFERENCES etablissements(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_pole) REFERENCES poles(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    INDEX idx_etablissement (id_etablissement),
    INDEX idx_pole (id_pole)
) ENGINE=InnoDB;

-- SECTEURS (A secteur can have multiple filieres)
CREATE TABLE secteurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_secteur VARCHAR(150) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- FILIERES (Associated with Etablissement, not directly with Pole)
CREATE TABLE filieres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_secteur INT NULL,
    nom_filiere VARCHAR(150) NOT NULL,
    id_pole INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (nom_filiere),
    FOREIGN KEY (id_secteur) REFERENCES secteurs(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    FOREIGN KEY (id_pole) REFERENCES poles(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    INDEX idx_secteur (id_secteur),
    INDEX idx_pole (id_pole)
) ENGINE=InnoDB;

-- GROUPES
CREATE TABLE groupes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_groupe VARCHAR(100) NOT NULL,
    annee VARCHAR(20) NOT NULL,
    niveau ENUM('TS','T','Q','S','FQ','BP','PC') NOT NULL,
    creneau ENUM('CDJ','CDS') DEFAULT 'CDJ' NOT NULL,
    id_filiere INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_filiere) REFERENCES filieres(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    INDEX idx_filiere (id_filiere)
) ENGINE=InnoDB;

-- MODULES
CREATE TABLE modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_module VARCHAR(150) NOT NULL,
    volume_horaire INT NOT NULL,
    id_filiere INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_filiere) REFERENCES filieres(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    INDEX idx_filiere (id_filiere)
) ENGINE=InnoDB;

-- MODULE_GROUPE_FORMATEUR (Many-to-Many between Modules and Formateurs with Groupes, establishment-scoped)
CREATE TABLE module_groupe_formateur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_module INT NOT NULL,
    id_groupe INT NOT NULL,
    id_formateur INT NOT NULL,
    id_etablissement INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_module) REFERENCES modules(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_groupe) REFERENCES groupes(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_formateur) REFERENCES formateurs(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_etablissement) REFERENCES etablissements(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    INDEX idx_module (id_module),
    INDEX idx_groupe (id_groupe),
    INDEX idx_formateur (id_formateur),
    INDEX idx_etablissement (id_etablissement)
) ENGINE=InnoDB;

-- EMPLOI DU TEMPS
CREATE TABLE emploi_du_temps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_seance DATE NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    id_module_groupe_formateur INT NOT NULL,
    id_module INT NULL,
    id_salle INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_module_groupe_formateur) REFERENCES module_groupe_formateur(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_module) REFERENCES modules(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    FOREIGN KEY (id_salle) REFERENCES salles(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    INDEX idx_date (date_seance),
    INDEX idx_salle (id_salle),
    INDEX idx_module (id_module),
    INDEX idx_module_groupe_formateur (id_module_groupe_formateur)
) ENGINE=InnoDB;

-- INDEXES FOR PERFORMANCE
CREATE INDEX idx_module_groupe ON module_groupe_formateur(id_module, id_groupe);
CREATE INDEX idx_formateur_module ON module_groupe_formateur(id_formateur, id_module);
CREATE INDEX idx_pole_chef ON poles(id_chef);
CREATE INDEX idx_pole_etablissement ON poles(id_etablissement);
CREATE INDEX idx_salle_etablissement ON salles(id_etablissement);
-- CREATE INDEX idx_filiere_etablissement ON filieres(id_etablissement);
CREATE INDEX idx_filiere_secteur ON filieres(id_secteur);
CREATE INDEX idx_groupe_filiere ON groupes(id_filiere);
CREATE INDEX idx_module_filiere ON modules(id_filiere);
CREATE INDEX idx_formateur_etablissement ON formateurs(id_etablissement);
CREATE INDEX idx_user_etablissement ON users(id_etablissement);

-- Prevent same salle booked at same time
ALTER TABLE emploi_du_temps
ADD CONSTRAINT unique_salle_time
UNIQUE (date_seance, heure_debut, heure_fin, id_salle);

-- Prevent invalid sessions
ALTER TABLE emploi_du_temps
ADD CONSTRAINT chk_time_valid
CHECK (heure_fin > heure_debut);

-- FILIERE MODULE PHASE ORDERING (for prerequisite planning and auto scheduling)
CREATE TABLE filiere_module_phases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_filiere INT NOT NULL,
    id_module INT NOT NULL,
    id_previous_module INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY filiere_module_unique (id_filiere, id_module),
    FOREIGN KEY (id_filiere) REFERENCES filieres(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_module) REFERENCES modules(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_previous_module) REFERENCES modules(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    INDEX idx_filiere_previous_module (id_filiere, id_previous_module)
) ENGINE=InnoDB;
