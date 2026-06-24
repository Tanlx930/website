# Responsive Web Design &amp; Development (RWDD)

> A fully responsive multi-page web application backed by a MySQL database — mobile-first layout, semantic HTML, and progressive enhancement.

![HTML5](https://img.shields.io/badge/HTML-5-E34F26?logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS-3-1572B6?logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6-F7DF1E?logo=javascript&logoColor=black)
![PHP](https://img.shields.io/badge/Backend-PHP-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/Database-MySQL-4479A1?logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/license-Academic-blue)

---

## Overview

A complete responsive web application built using **HTML, CSS, JavaScript, PHP, and MySQL**. The site adopts a **mobile-first** design philosophy with fluid grids, flexible images, and CSS media queries to deliver a consistent experience across phones, tablets, and desktops. Server-side logic in PHP handles user accounts, content CRUD, and form submissions, with MySQL as the persistent store.

---

## Module / Course

- **Module Code:** AAPP012-4-2-RWDD
- **Course:** Responsive Web Design &amp; Development
- **Institution:** Asia Pacific University
- **Project Type:** Group Assignment

---

## Key Features

- **Mobile-first responsive layout** — flexbox + grid + media queries.
- **Semantic HTML5** — accessible structure (header, nav, main, footer, aria).
- **Server-side PHP backend** — user auth, CRUD, form validation.
- **MySQL persistence** — relational schema with foreign keys.
- **Progressive enhancement** — works without JS; JS adds interactivity.
- **Cross-browser compatibility** — tested on Chrome, Firefox, Edge, Safari.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Markup | HTML5 (semantic) |
| Styling | CSS3 (Flexbox, Grid, media queries) |
| Interactivity | Vanilla JavaScript (ES6+) |
| Backend | PHP |
| Database | MySQL |
| Server | Apache (XAMPP / WAMP) |

---

## Project Structure

```
rwdd/
├── index.html / pages...   # HTML pages
├── css/                    # Stylesheets (mobile-first)
├── js/                     # Client-side scripts
├── php/                    # PHP backend (auth, CRUD, db helpers)
├── images/                 # Site images
├── rwdd.sql                # Database schema + seed data
└── docs/
    └── RWDD final report.pdf
```

---

## Getting Started

```bash
# 1. Install XAMPP / WAMP / LAMP

# 2. Place the project in your htdocs/ folder

# 3. Import the database schema
mysql -u root -p < rwdd.sql

# 4. Configure PHP DB credentials in php/config.php
# $host = "localhost"; $user = "root"; $pass = ""; $db = "rwdd";

# 5. Visit http://localhost/rwdd/
```

---

## Responsive Breakpoints

| Device | Breakpoint |
|---|---|
| Mobile | `< 600px` |
| Tablet | `600px – 1024px` |
| Desktop | `> 1024px` |

---

## Screenshots

> _Add screenshots showing the layout at mobile, tablet, and desktop sizes._

---

## Documentation

- `RWDD final report.pdf` — full project report
- `rwdd.sql` — database schema &amp; seed data
- `rwdd.xlsx` — supporting data
- `AAPP012-4-2-RWDD_Group_Assignment_Proposal_Template.docx` — proposal

---

## License

Academic project. Source provided for portfolio reference; not for commercial reuse.
