# SafeCommunities - Neighborhood Safety and Incident Reporting

A web application for reporting and tracking safety incidents in neighborhoods.

## Features

- Report safety incidents in your neighborhood
- View incidents on an interactive map
- Filter incidents by type and time period
- Mobile-responsive design
- PHP backend for data storage and retrieval

## Technologies Used

- HTML5
- CSS3 with Tailwind CSS
- JavaScript (vanilla)
- PHP
- MySQL
- Leaflet.js for maps

## Setup Instructions

### Prerequisites

- Web server with PHP 7.4+ support (Apache/Nginx)
- MySQL database
- Basic knowledge of web server configuration

### Installation Steps

1. **Clone or download the repository**

   Place all files in your web server's document root or a subdirectory.

2. **Set up the database**

   - Create a MySQL database for the application
   - Import the database schema by running the `db_setup.sql` file:
     ```
     mysql -u username -p database_name < db_setup.sql
     ```
   - Or run the SQL statements directly in your database management tool

3. **Configure the application**

   - Open `config.php` and update the database connection details:
     ```php
     $dbHost = 'your_database_host';
     $dbName = 'your_database_name';
     $dbUser = 'your_database_username';
     $dbPass = 'your_database_password';
     ```
   - Update other configuration settings as needed

4. **Create upload directory**

   - Create a directory named `uploads` in the application root
   - Make sure it's writable by the web server:
     ```
     mkdir uploads
     chmod 755 uploads
     ```

5. **Test the application**

   - Open the application in a web browser
   - Try submitting an incident report
   - View the incidents on the dashboard

## Usage

- **Home Page**: General information and recent incidents
- **Report Page**: Form to submit new safety incidents
- **Dashboard**: Interactive map and list of all incidents, with filtering options

## Security Considerations

This is a basic implementation for demonstration purposes. In a production environment, consider adding:

- User authentication
- CSRF protection
- Input validation
- Proper error handling
- Secure database access
- HTTPS implementation

## Future Enhancements

- User accounts and authentication
- Email notifications for new incidents
- Advanced filtering and search
- Incident verification workflow
- Community discussions on incidents
- Mobile app integration

## License

[MIT License](LICENSE)