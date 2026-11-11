USE tourisme_ivoire;
INSERT INTO transports(code,nom) VALUES ('bus','Bus'),('taxi','Taxi'),('taxi_communal','Taxi communal'),('vtc','VTC'),('bateau','Bateau'),('transport_touristique','Transport touristique'),('location_vehicule','Location de véhicules') ON DUPLICATE KEY UPDATE nom=VALUES(nom);
