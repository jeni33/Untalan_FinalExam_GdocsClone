CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,                     
    password VARCHAR(255) NOT NULL,                 
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user', 
    is_suspended BOOLEAN DEFAULT 0,                
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP   
);

CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,                    
    content LONGTEXT,                               
    owner_id INT NOT NULL,                          
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,  
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE document_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    user_id INT NOT NULL,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (document_id, user_id) 
);
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,                       
    user_id INT NOT NULL,                           
    action VARCHAR(255),                            
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,  
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,                      
    user_id INT NOT NULL,                           
    message TEXT,                                   
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,   
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
