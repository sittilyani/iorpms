ALTER TABLE laboratory
DROP FOREIGN KEY fk_mat_id,
ADD CONSTRAINT fk_mat_id FOREIGN KEY (mat_id) REFERENCES patients(mat_id) ON DELETE CASCADE;


# If already exists start with single statement to remove
   ALTER TABLE laboratory
   DROP FOREIGN KEY fk_mat_id;

# After removing now insert this statement -- in this example, the table is "laboratory"
    ALTER TABLE laboratory
    ADD CONSTRAINT fk_mat_id FOREIGN KEY (mat_id) REFERENCES patients(mat_id) ON DELETE CASCADE;

# ALTER TABLE followup_psychiatric_form CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;