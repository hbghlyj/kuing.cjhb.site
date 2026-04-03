# Lean Online Judge (Lean OJ)

The Lean Online Judge is a specialized platform for hosting and evaluating coding problems written in the **Lean 4** theorem prover. It provides a collaborative environment for problem creation, submission tracking, and automated evaluation.

---

## 🏗️ Backend Services
The system operates as a set of interconnected services on the Linux server:

1.  **`php8.4-fpm.service`**: The core web backend that serves the application interface and handles the RESTful API / UI routing.
2.  **`leanoj-worker.service`**: A persistent background runner (`worker.php`) that polls for new submissions and coordinates the lean compilation process.
3.  **`isolate.service`**: Manages the `cgroup` hierarchy used by the Isolate sandbox. This ensures that every Lean 4 submission is executed in a secure environment with strict resource limits (RAM/CPU).

---

## 🗄️ Database Schema
The application uses a centered SQLite database. The current schema is as follows:

```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL
);

CREATE TABLE answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    imports TEXT,
    body TEXT
);

CREATE TABLE problems (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    statement TEXT NOT NULL,
    template TEXT NOT NULL,
    answer INTEGER,
    contest INTEGER, -- Legacy field
    owner_id INTEGER REFERENCES users(id),
    FOREIGN KEY(answer) REFERENCES answers(id)
);

CREATE TABLE submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    problem INTEGER NOT NULL,
    user INTEGER NOT NULL,
    source TEXT NOT NULL,
    status TEXT NOT NULL,
    time TEXT NOT NULL,
    FOREIGN KEY(problem) REFERENCES problems(id),
    FOREIGN KEY(user) REFERENCES users(id)
);

CREATE TABLE problem_revisions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    problem_id INTEGER NOT NULL REFERENCES problems(id),
    statement TEXT NOT NULL,
    template TEXT NOT NULL,
    user_id INTEGER REFERENCES users(id),
    time TEXT NOT NULL
);
```

---

## 📜 Revision History & Collaborative Editing
Lean OJ features a robust version tracking system for problem statement and templates:

- **Revision Log**: Every modification to a problem's statement or Lean template creates a snapshot in `problem_revisions`.
- **Version Comparison**: Users can view side-by-side diffs (powered by `jsdiff`) to see exactly what changed in any specific version compared to the current state.
- **Rollback System**: Problems can be instantly reverted to any historical revision. Performing a rollback creates a new revision entry to maintain a full audit trail.

---

## 🔐 Permissions & Ownership
- **Problem Ownership**: When a user creates a problem, they are designated as the owner.
- **Admin Rights**: 
    - Full system control (Delete any problem, submission, or revision).
    - Can modify restricted problem fields like **Title** and **Answer ID**.
- **User Rights**: 
    - Can edit any problem's statement and template.
    - Can delete their own problems and their own submissions.
