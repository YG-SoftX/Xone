export default {
    content: ['./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php', './storage/framework/views/*.php', './resources/**/*.blade.php', './resources/**/*.jsx'],
    theme: { extend: { fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] } } },
    plugins: [require('@tailwindcss/forms')],
};
