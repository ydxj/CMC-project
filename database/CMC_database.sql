CREATE DATABASE cmc;
USE cmc;

-- USERS TABLE
CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('DIRECTEUR', 'CHEF_POLE', 'FORMATEUR') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- POLES
CREATE TABLE poles (
    id_pole INT AUTO_INCREMENT PRIMARY KEY,
    nom_pole VARCHAR(100) NOT NULL UNIQUE,
    id_chef INT UNIQUE,
    FOREIGN KEY (id_chef) REFERENCES users(id_user)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

-- SALLES (Belong to Pole)
CREATE TABLE salles (
    id_salle INT AUTO_INCREMENT PRIMARY KEY,
    nom_salle VARCHAR(100) NOT NULL,
    type_salle ENUM('SALLE', 'ATELIER') NOT NULL,
    id_pole INT NOT NULL,
    UNIQUE (nom_salle, id_pole),
    FOREIGN KEY (id_pole) REFERENCES poles(id_pole)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- FORMATIONS
CREATE TABLE formations (
    id_formation INT AUTO_INCREMENT PRIMARY KEY,
    nom_formation VARCHAR(150) NOT NULL,
    id_pole INT NOT NULL,
    FOREIGN KEY (id_pole) REFERENCES poles(id_pole)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- GROUPES
CREATE TABLE groupes (
    id_groupe INT AUTO_INCREMENT PRIMARY KEY,
    nom_groupe VARCHAR(100) NOT NULL,
    annee VARCHAR(20) NOT NULL,
    id_formation INT NOT NULL,
    FOREIGN KEY (id_formation) REFERENCES formations(id_formation)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- MODULES
CREATE TABLE modules (
    id_module INT AUTO_INCREMENT PRIMARY KEY,
    nom_module VARCHAR(150) NOT NULL,
    volume_horaire INT NOT NULL,
    id_formation INT NOT NULL,
    id_formateur INT NOT NULL,
    FOREIGN KEY (id_formation) REFERENCES formations(id_formation)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- MODULE_FORMATEUR (Many-to-Many between Modules and Formateurs)
CREATE TABLE module_formateur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_module INT NOT NULL,
    id_formateur INT NOT NULL,
    FOREIGN KEY (id_module) REFERENCES modules(id_module)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_formateur) REFERENCES users(id_user)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- EMPLOI DU TEMPS
CREATE TABLE emploi_du_temps (
    id_seance INT AUTO_INCREMENT PRIMARY KEY,
    date_seance DATE NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    id_groupe INT NOT NULL,
    id_module INT NOT NULL,
    id_formateur INT NOT NULL,
    id_salle INT NOT NULL,
    FOREIGN KEY (id_groupe) REFERENCES groupes(id_groupe)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_module) REFERENCES modules(id_module)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_formateur) REFERENCES users(id_user)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_salle) REFERENCES salles(id_salle)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- PERFORMANCE INDEXES
CREATE INDEX idx_date ON emploi_du_temps(date_seance);
CREATE INDEX idx_groupe ON emploi_du_temps(id_groupe);
CREATE INDEX idx_formateur ON emploi_du_temps(id_formateur);
CREATE INDEX idx_salle ON emploi_du_temps(id_salle);

-- Prevent same salle booked at same time
ALTER TABLE emploi_du_temps
ADD CONSTRAINT unique_salle_time
UNIQUE (date_seance, heure_debut, heure_fin, id_salle);

-- Prevent same formateur teaching two sessions at same time
ALTER TABLE emploi_du_temps
ADD CONSTRAINT unique_formateur_time
UNIQUE (date_seance, heure_debut, heure_fin, id_formateur);

-- Prevent same groupe having two sessions at same time
ALTER TABLE emploi_du_temps
ADD CONSTRAINT unique_groupe_time
UNIQUE (date_seance, heure_debut, heure_fin, id_groupe);