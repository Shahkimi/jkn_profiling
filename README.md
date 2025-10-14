# JKN Profiling System

A comprehensive hospital information and staffing analytics system for Malaysian healthcare institutions. This system provides detailed hospital profiles, clinical services information, and staffing data analytics through an intuitive web interface.

## 🏥 Features

### Hospital Information System
- **Hospital Profiles**: Comprehensive hospital information including contact details, services, and operational data
- **Clinical Services**: Detailed listing of available medical services and specialties
- **Real-time Data**: Automatic data synchronization from Google Sheets
- **Responsive Design**: Modern, mobile-friendly interface with Tailwind CSS

### Staffing Analytics Dashboard
- **Interactive Analytics**: Visual charts and graphs for staffing data analysis
- **Position Management**: Track positions, vacancies, and staffing levels
- **Department Insights**: Analyze staffing across different hospital departments
- **Export Capabilities**: Generate reports and export data

### Technical Features
- **Smart Caching**: Intelligent cache management with background refresh
- **Performance Optimized**: Fast loading with efficient data handling
- **Security**: Built-in security headers and CSRF protection
- **Rate Limiting**: API rate limiting for stability
- **Error Handling**: Comprehensive error logging and handling

## 🚀 Quick Start

### Prerequisites
- PHP 7.4 or higher
- Web server (Apache/Nginx)
- Internet connection for Google Sheets integration

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-username/jkn_profiling.git
   cd jkn_profiling
   ```

2. **Configure environment variables**
   ```bash
   cp .env.example .env
   # Edit .env with your configuration
   ```

3. **Set up cache directory**
   ```bash
   mkdir -p cache
   chmod 755 cache
   ```

4. **Configure web server**
   Point your web server document root to the project directory.

5. **Set up cache warmer (optional)**
   ```bash
   chmod +x setup_cron.sh
   ./setup_cron.sh
   ```

## ⚙️ Configuration

### Environment Variables

Edit the `.env` file to configure your system:

```env
# Application Configuration
APP_NAME=Hospital Information System
APP_ENV=production
APP_DEBUG=false

# Google Sheets Configuration
GOOGLE_SHEETS_URL=your_google_sheets_csv_export_url
GOOGLE_SHEETS_TIMEOUT=10

# Cache Configuration
CACHE_ENABLED=true
CACHE_DIRECTORY=cache
CACHE_EXPIRY=3600

# Security Configuration
SECURITY_HEADERS_ENABLED=true
CSRF_TOKEN_ENABLED=true
RATE_LIMIT_ENABLED=true
```

### Google Sheets Setup

1. Create a Google Sheet with hospital data
2. Ensure the sheet has proper column headers (PTJ, services, etc.)
3. Get the CSV export URL: `File > Share > Publish to web > CSV`
4. Add the URL to `GOOGLE_SHEETS_URL` in your `.env` file

## 📁 Project Structure

```
jkn_profiling/
├── index.php              # Main hospital information system
├── cache_warmer.php       # Background cache refresh script
├── setup_cron.sh         # Cron job setup script
├── .env                  # Environment configuration
├── cache/                # Cache directory
│   ├── hospitals_data.json
│   └── cache_refresh.log
└── perjawatan/           # Staffing analytics module
    ├── index.php         # Staffing dashboard
    ├── b.php            # Data processing
    └── original.php     # Original implementation
```

## 🖥️ Usage

### Hospital Information System

1. **Access the main system**
   ```
   http://your-domain.com/
   ```

2. **Navigate hospitals**
   - Use the hospital selector to browse different institutions
   - View detailed hospital profiles and services
   - Access contact information and operational details

### Staffing Analytics Dashboard

1. **Access the staffing module**
   ```
   http://your-domain.com/perjawatan/
   ```

2. **Analyze staffing data**
   - View interactive charts and analytics
   - Filter by department, position, or hospital
   - Export reports and data visualizations

## 🔧 Cache Management

### Automatic Cache Refresh
The system includes intelligent cache management:
- Cache expires after 1 hour (configurable)
- Background refresh prevents user delays
- Lock files prevent concurrent refresh operations

### Manual Cache Operations

**Warm cache manually:**
```bash
php cache_warmer.php
```

**Clear cache:**
```bash
rm cache/hospitals_data.json
```

**View cache logs:**
```bash
tail -f cache/cache_refresh.log
```

## 🛡️ Security Features

- **CSRF Protection**: Token-based CSRF protection
- **Security Headers**: X-Frame-Options, X-Content-Type-Options, etc.
- **Rate Limiting**: Configurable request rate limiting
- **Input Validation**: Sanitized data processing
- **Error Handling**: Secure error logging without data exposure

## 📊 API Endpoints

### Hospital Data
- `GET /` - Main hospital interface
- `GET /?hospital=N` - Specific hospital profile (N = hospital index)

### Staffing Data
- `GET /perjawatan/` - Staffing analytics dashboard
- Various internal endpoints for data processing

## 🔍 Monitoring & Logging

### Log Files
- `cache/cache_refresh.log` - Cache operations log
- `logs/error.log` - Application error log (if configured)

### Health Checks
Monitor these indicators:
- Cache file timestamp
- Google Sheets connectivity
- Error log entries
- Response times

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- Create an issue on GitHub
- Check the logs for error details
- Verify Google Sheets connectivity
- Ensure proper file permissions

## 🔄 Updates & Maintenance

### Regular Maintenance
- Monitor cache performance
- Update Google Sheets data structure as needed
- Review security configurations
- Check log files for errors

### Version Updates
- Backup your `.env` configuration
- Test in staging environment
- Update dependencies if needed
- Monitor after deployment

---

**Built with ❤️ for Malaysian Healthcare System**