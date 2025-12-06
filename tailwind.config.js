/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./resources/**/*.blade.php",
        // "./resources/**/*.js",
        // "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            colors: {
                'abu': '#4B4B4B',
                'biru': '#3BB6FF',
                'biru-transparent': '#3bb6ff29'
            }
        },
    },
    plugins: [
        require('@tailwindcss/typography'),
    ],
}