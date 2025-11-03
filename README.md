# Hospital Information System (JKN Profiling)

A web-based hospital information system that fetches and displays hospital data from Google Sheets with intelligent caching and a modern user interface.

## 🏥 Features

- **Real-time Data Fetching**: Automatically retrieves hospital data from Google Sheets
- **Intelligent Caching**: Background cache refresh system to ensure data freshness
- **Modern UI**: Professional animated sidebar with responsive design
- **Security Features**: Built-in CSRF protection, rate limiting, and security headers
- **Performance Optimized**: Efficient caching mechanism with configurable expiry times
- **Error Handling**: Comprehensive error logging and graceful fallbacks

## 🚀 Quick Start

### Prerequisites

- PHP 7.4 or higher
- Web server (Apache/Nginx)
- Access to Google Sheets (for data source)

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd jkn_profiling
   ```

2. **Configure environment variables**
   ```bash
   cp .env.example .env
   ```
   Edit `.env` file with your configuration:
   ```env
   GOOGLE_SHEETS_URL=your_google_sheets_csv_export_url
   CACHE_EXPIRY=3600
   APP_ENV=production
   ```

3. **Set up permissions**
   ```bash
   chmod 755 cache/
   chmod +x setup_cron.sh
   ```

4. **Configure cron job for cache warming**
   ```bash
   ./setup_cron.sh
   ```
   Or manually add to crontab:
   ```bash
   0 * * * * /usr/bin/php /path/to/project/cache_warmer.php
   ```

5. **Start your web server** and navigate to the project directory

## 📁 Project Structure

```
jkn_profiling/
├── index.php              # Main application entry point
├── cache_warmer.php       # Background cache refresh script
├── setup_cron.sh         # Cron job setup script
├── .env                  # Environment configuration
├── assets/
│   ├── css/
│   │   └── main.css      # Main stylesheet
│   └── js/
│       ├── main.js       # Main JavaScript functionality
│       └── tailwind-config.js
├── cache/
│   ├── hospitals_data.json    # Cached hospital data
│   └── cache_refresh.log      # Cache refresh logs
```

## ⚙️ Configuration

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `GOOGLE_SHEETS_URL` | CSV export URL from Google Sheets | Required |
| `CACHE_EXPIRY` | Cache expiration time in seconds | 3600 |
| `CACHE_DIRECTORY` | Directory for cache files | cache |
| `RATE_LIMIT_MAX_REQUESTS` | Max requests per time window | 60 |
| `RATE_LIMIT_TIME_WINDOW` | Rate limit time window in seconds | 3600 |
| `CSRF_TOKEN_ENABLED` | Enable CSRF protection | true |
| `ERROR_LOGGING_ENABLED` | Enable error logging | true |

### Google Sheets Setup

1. Create a Google Sheet with hospital data
2. Ensure the first row contains headers starting with 'PTJ'
3. Get the CSV export URL: `File > Share > Publish to web > CSV`
4. Add the URL to your `.env` file

## 🔧 Usage

### Manual Cache Refresh
```bash
php cache_warmer.php
```

### Check Cache Status
The system automatically manages cache freshness, but you can monitor it through:
- Cache logs: `cache/cache_refresh.log`
- Cache file: `cache/hospitals_data.json`

## 🛡️ Security Features

- **CSRF Protection**: Prevents cross-site request forgery attacks
- **Rate Limiting**: Protects against abuse and DoS attacks
- **Security Headers**: Implements security best practices
- **Input Validation**: Sanitizes and validates all user inputs
- **Error Handling**: Secure error messages without information disclosure

## 🔄 Cache Management

The system uses a sophisticated caching mechanism:

1. **Automatic Refresh**: Cache is refreshed in the background when expired
2. **Lock Prevention**: Prevents multiple simultaneous cache refreshes
3. **Graceful Fallback**: Uses stale cache if refresh fails
4. **Logging**: All cache operations are logged for monitoring

## 📊 Monitoring

### Log Files
- `cache/cache_refresh.log` - Cache refresh operations
- `logs/error.log` - Application errors (if configured)

### Health Checks
- Cache freshness indicator in the UI
- Background process monitoring via log files

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

If you encounter any issues or have questions:

1. Check the logs in `cache/cache_refresh.log`
2. Verify your `.env` configuration
3. Ensure Google Sheets URL is accessible
4. Check file permissions for the cache directory

## 🔮 Roadmap

- [ ] Database integration option
- [ ] Advanced filtering and search
- [ ] Export functionality
- [ ] Multi-language support
- [ ] API endpoints for mobile apps
- [ ] Real-time notifications

---

**Made with ❤️ for healthcare data management**
