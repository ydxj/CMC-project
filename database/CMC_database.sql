CREATE DATABASE cmc;
USE cmc;

-- USERS TABLE
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    matricule VARCHAR(50) UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('DIRECTEUR', 'CHEF_POLE', 'FORMATEUR') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- FORMATEUR
CREATE TABLE formateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL UNIQUE,
    type_formateur ENUM('FP','FV'),
    MHS INT,
    NBR_semaine INT,
    FOREIGN KEY (id_user) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- POLES
CREATE TABLE poles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_pole VARCHAR(100) NOT NULL UNIQUE,
    id_chef INT UNIQUE,
    FOREIGN KEY (id_chef) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- formateur_pole (Many-to-Many between formateur and Poles)
CREATE TABLE formateur_pole (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_pole INT NOT NULL,
    FOREIGN KEY (id_user) REFERENCES formateurs(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_pole) REFERENCES poles(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- SALLES (Belong to Pole)
CREATE TABLE salles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_salle VARCHAR(100) NOT NULL,
    type_salle ENUM('SALLE', 'ATELIER') NOT NULL,
    id_pole INT NOT NULL,
    UNIQUE (nom_salle, id_pole),
    FOREIGN KEY (id_pole) REFERENCES poles(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- FILIERES
CREATE TABLE filieres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_filiere VARCHAR(150) NOT NULL,
    id_pole INT NOT NULL,
    FOREIGN KEY (id_pole) REFERENCES poles(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- GROUPES
CREATE TABLE groupes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_groupe VARCHAR(100) NOT NULL,
    annee VARCHAR(20) NOT NULL,
    niveau ENUM('TS','T','Q','S','FQ','BP','PC') NOT NULL,
    creneau ENUM('CDJ','CDS') DEFAULT 'CDJ' NOT NULL,
    id_filiere INT NOT NULL,
    FOREIGN KEY (id_filiere) REFERENCES filieres(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- MODULES
CREATE TABLE modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_module VARCHAR(150) NOT NULL,
    volume_horaire INT NOT NULL,
    id_filiere INT NOT NULL,
    FOREIGN KEY (id_filiere) REFERENCES filieres(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- MODULE_GROUPE_FORMATEUR (Many-to-Many between Modules and Formateurs with Groupes)
CREATE TABLE module_groupe_formateur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_module INT NOT NULL,
    id_groupe INT NOT NULL,
    id_formateur INT NOT NULL,
    FOREIGN KEY (id_module) REFERENCES modules(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_groupe) REFERENCES groupes(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_formateur) REFERENCES formateurs(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- EMPLOI DU TEMPS
CREATE TABLE emploi_du_temps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_seance DATE NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    id_module_groupe_formateur INT NOT NULL,
    id_salle INT NOT NULL,
    FOREIGN KEY (id_module_groupe_formateur) REFERENCES module_groupe_formateur(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_salle) REFERENCES salles(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    INDEX idx_date (date_seance),
    INDEX idx_salle (id_salle),
    INDEX idx_module_groupe_formateur (id_module_groupe_formateur)
) ENGINE=InnoDB;

-- INDEXES FOR PERFORMANCE
CREATE INDEX idx_module_groupe ON module_groupe_formateur(id_module, id_groupe);
CREATE INDEX idx_formateur_module ON module_groupe_formateur(id_formateur, id_module);
CREATE INDEX idx_pole_chef ON poles(id_chef);
CREATE INDEX idx_salle_pole ON salles(id_pole);
CREATE INDEX idx_filiere_pole ON filieres(id_pole);
CREATE INDEX idx_groupe_filiere ON groupes(id_filiere);
CREATE INDEX idx_module_filiere ON modules(id_filiere);

-- Prevent same salle booked at same time
ALTER TABLE emploi_du_temps
ADD CONSTRAINT unique_salle_time
UNIQUE (date_seance, heure_debut, heure_fin, id_salle);
