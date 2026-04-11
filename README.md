# Made for Keeps - Child's Archive Platform

A beautiful, responsive PHP application for preserving and archiving your child's precious moments, stories, and voice recordings. Built with Bootstrap 5 and a clean, modern design.

## 🎯 Features

- ✨ **Beautiful Design** - Modern, responsive interface matching the Figma design exactly
- 👨‍👩‍👧‍👦 **Family Profiles** - Create and manage multiple children's archives
- 📖 **Story Archives** - Organize memories into stories with detailed entries
- 🔐 **Secure & Private** - All family memories are protected with secure authentication
- 📝 **Rich Entries** - Support for text entries, photos, audio, and video (expandable)
- 🎨 **Responsive UI** - Optimized for mobile, tablet, and desktop viewing

## 🛠 Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: Bootstrap 5, HTML5, CSS3, JavaScript
- **Typography**: Google Fonts (Playfair Display, Poppins, Lora)
- **Design System**: Custom CSS with CSS variables for consistency

## 📁 Project Structure

```
made-for-keeps/
├── public/
│   ├── index.php                  # Landing page (home)
│   ├── css/
│   │   └── style.css              # All styles matching Figma design
│   ├── js/
│   │   └── main.js                # JavaScript utilities
│   ├── images/                    # Image assets
│   └── uploads/                   # User uploaded files
├── src/
│   ├── config/
│   │   └── database.php           # Database connection
│   ├── Auth.php                   # Authentication class
│   └── helpers.php                # Helper functions
├── pages/
│   ├── login.php                  # Login page
│   ├── register.php               # Registration page
│   ├── dashboard.php              # User dashboard
│   ├── create-story.php           # Create new story
│   ├── view-child.php             # View child's stories
│   ├── view-story.php             # View story details
│   ├── add-entry.php              # Add entry to story
│   └── logout.php                 # Logout action
├── database/
│   └── schema.sql                 # Complete database schema
├── .env.example                   # Example environment variables
├── .env                           # Local environment variables
├── .gitignore                     # Git ignore rules
└── README.md                      # This file
```

## 🚀 Quick Start

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- A web server (Apache, Nginx, or PHP built-in)

### Installation Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd made-for-keeps
   ```

2. **Setup environment variables**
   ```bash
   cp .env.example .env
   ```

3. **Update `.env` with your database credentials**
   ```env
   DB_HOST=localhost
   DB_USER=root
   DB_PASSWORD=your_password
   DB_NAME=made_for_keeps
   APP_URL=http://localhost:8000
   ```

4. **Create the database**
   ```bash
   mysql -u root -p < database/schema.sql
   ```

5. **Create upload directory**
   ```bash
   mkdir -p public/uploads
   chmod 755 public/uploads
   ```

6. **Start the development server**
   ```bash
   cd public
   php -S localhost:8000
   ```

7. **Visit the application**
   Open your browser and go to: `http://localhost:8000`

## 📱 User Flow

1. **Landing Page** - Browse the beautiful landing page
2. **Register** - Create a new account with email/password
3. **Login** - Access your personal dashboard
4. **Create Story** - Start a new story for your child
5. **Add Entries** - Add text notes, photos, audio, or videos
6. **View Archive** - Browse and manage your family's memories

## 🎨 Design Specifications

### Color Palette (From Figma)

```css
--bg-cream: #E8E4DC;           /* Main background */
--bg-light-cream: #F5F2ED;     /* Light accents */
--btn-sage: #7A956B;           /* Primary button */
--btn-sage-hover: #6B8460;     /* Button hover */
--text-dark: #333333;          /* Main text */
--text-muted: #666666;         /* Secondary text */
--footer-bg: #4A4A4A;          /* Footer background */
--border-color: #E0E0E0;       /* Borders */
```

### Typography (From Google Fonts)

- **Headings**: Playfair Display (700) - Serif
- **Body Text**: Poppins (400, 500, 600) - Sans-serif
- **Quotes**: Lora (italic) - Serif

### Responsive Breakpoints

- **Mobile**: < 576px
- **Tablet**: 768px - 991px
- **Desktop**: > 992px

## 🔐 Security Features

- ✅ **Password Security** - BCrypt hashing for all passwords
- ✅ **CSRF Protection** - Token validation on all forms
- ✅ **SQL Injection Prevention** - Prepared statements throughout
- ✅ **XSS Protection** - HTML entity escaping on all output
- ✅ **Session Security** - Secure session handling
- ✅ **Authorization** - User ownership verification for all data access

## 🗄️ Database Schema

### Users Table
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | User ID |
| email | VARCHAR(255) UNIQUE | User email |
| password | VARCHAR(255) | Hashed password |
| full_name | VARCHAR(255) | User's full name |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

### Children Table
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Child ID |
| user_id | INT FK | Parent user ID |
| name | VARCHAR(255) | Child's name |
| date_of_birth | DATE | Child's DOB |
| created_at | TIMESTAMP | Creation timestamp |

### Stories Table
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Story ID |
| child_id | INT FK | Associated child |
| title | VARCHAR(255) | Story title |
| description | TEXT | Story description |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Last update |

### Story Entries Table
| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Entry ID |
| story_id | INT FK | Associated story |
| entry_type | ENUM | text/audio/image/video |
| content | LONGTEXT | Text content |
| file_path | VARCHAR(500) | File location |
| created_at | TIMESTAMP | Creation timestamp |

## 🔮 Future Enhancements

- 📸 **Photo Upload** - Upload and organize family photos
- 🎤 **Audio Recording** - Record and store voice memos
- 🎬 **Video Upload** - Store video memories
- 👥 **Family Sharing** - Invite family members to contribute
- 📧 **Email Notifications** - Get notified of new entries
- 🔔 **Anniversary Reminders** - Celebrate milestones
- 📱 **Mobile App** - Native iOS/Android app
- 🎁 **Digital Books** - Generate digital books from stories
- 🔍 **Full Search** - Search across all entries
- 📊 **Timeline View** - Visual timeline of memories
- 💾 **Backup & Export** - Download your memories
- 🎵 **Music Integration** - Add background music

## 📋 File Descriptions

### Key Files

- **`public/index.php`** - Main landing page with split layout
- **`public/css/style.css`** - Complete design system with all styles
- **`src/config/database.php`** - Database connection and configuration
- **`src/Auth.php`** - Authentication and user management
- **`src/helpers.php`** - Reusable helper functions
- **`database/schema.sql`** - Full database setup script

## 🤝 Contributing

Contributions are welcome! Please feel free to submit pull requests with improvements.

## 📄 License

This project is open source and available under the MIT License.

## 🆘 Support

For issues, feature requests, or questions, please open an issue in the repository.

---

**Made for Keeps** - *A place to preserve their stories, their voice and the moments you never want to forget.*