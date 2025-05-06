import { Head, Link } from '@inertiajs/react';

export default function Welcome({ auth, laravelVersion, phpVersion }) {
    return (
        <>
            <Head title="GestVenin | Gestión de Ventas e Inventario" />
            <div className="bg-gradient-to-br from-blue-50 to-blue-100 min-h-screen dark:from-gray-900 dark:to-gray-800">
                {/* Hero Header */}
                <header className="relative pt-6 pb-16 sm:pb-24">
                    <div className="px-4 mx-auto max-w-7xl sm:px-6">
                        <nav className="relative flex items-center justify-between sm:h-10">
                            <div className="flex items-center flex-1">
                                <div className="flex items-center justify-between w-full md:w-auto">
                                    {/* Logo */}
                                    <div className="flex items-center">
                                        <span className="text-4xl font-extrabold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-emerald-500 tracking-tight">
                                            Gest<span className="text-orange-500">Venin</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div className="hidden md:block md:ml-10 md:space-x-8">
                                {auth.user ? (
                                    <Link
                                        href={route('dashboard')}
                                        className="px-6 py-3 text-base font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                                    >
                                        Dashboard
                                    </Link>
                                ) : (
                                    <Link
                                        href={route('login')}
                                        className="px-6 py-3 text-base font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                                    >
                                        Iniciar Sesión
                                    </Link>
                                )}
                            </div>
                        </nav>
                    </div>

                    {/* Hero Content */}
                    <div className="relative mt-10 md:mt-16">
                        <div className="px-4 mx-auto max-w-7xl sm:px-6">
                            <div className="text-center">
                                <h1 className="text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl md:text-6xl lg:text-7xl dark:text-white">
                                    <span className="block">Potencia tu negocio con</span>
                                    <span className="block mt-2 bg-clip-text text-transparent bg-gradient-to-r from-blue-600 via-emerald-500 to-orange-500">
                                        GestVenin
                                    </span>
                                </h1>
                                <p className="max-w-md mx-auto mt-5 text-lg text-gray-600 md:text-xl md:max-w-3xl dark:text-gray-300">
                                    La solución integral para la gestión de ventas e inventario que tu empresa necesita.
                                    Simplifica, optimiza y haz crecer tu negocio.
                                </p>
                                <div className="max-w-md mx-auto mt-8 sm:flex sm:justify-center">
                                    {auth.user ? (
                                        <div className="rounded-md shadow">
                                            <Link
                                                href={route('dashboard')}
                                                className="flex items-center justify-center w-full px-8 py-3 text-base font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 md:py-4 md:text-lg md:px-10 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                                            >
                                                Ir al Dashboard
                                            </Link>
                                        </div>
                                    ) : (
                                        <div className="rounded-md shadow">
                                            <Link
                                                href={route('login')}
                                                className="flex items-center justify-center w-full px-8 py-3 text-base font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 md:py-4 md:text-lg md:px-10 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                                            >
                                                Comenzar Ahora
                                            </Link>
                                        </div>
                                    )}
                                    <div className="mt-3 sm:mt-0 sm:ml-3">
                                        <a
                                            href="#features"
                                            className="flex items-center justify-center w-full px-8 py-3 text-base font-medium rounded-lg border border-transparent text-gray-700 bg-gray-100 hover:bg-gray-200 md:py-4 md:text-lg md:px-10 transition-all shadow hover:shadow-lg dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                        >
                                            Conocer Más
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                {/* Features Section */}
                <section id="features" className="py-16 bg-white dark:bg-gray-800">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="lg:text-center">
                            <p className="text-base font-semibold text-blue-600 tracking-wide uppercase dark:text-blue-400">
                                Características
                            </p>
                            <h2 className="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl dark:text-white">
                                Gestión inteligente para tu negocio
                            </h2>
                            <p className="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto dark:text-gray-300">
                                Todo lo que necesitas para administrar tus ventas e inventario en un solo lugar.
                            </p>
                        </div>

                        <div className="mt-16">
                            <div className="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                                {/* Feature 1 */}
                                <div className="bg-gray-50 p-6 rounded-xl shadow-lg transform transition duration-500 hover:scale-105 dark:bg-gray-700">
                                    <div className="rounded-full bg-blue-100 w-12 h-12 flex items-center justify-center mb-4 dark:bg-blue-900">
                                        <svg className="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <h3 className="text-lg font-medium text-gray-900 mb-2 dark:text-white">Gestión de Ventas</h3>
                                    <p className="text-gray-500 dark:text-gray-300">
                                        Control completo sobre tus ventas, clientes y facturación. Analiza tendencias y toma decisiones informadas.
                                    </p>
                                </div>

                                {/* Feature 2 */}
                                <div className="bg-gray-50 p-6 rounded-xl shadow-lg transform transition duration-500 hover:scale-105 dark:bg-gray-700">
                                    <div className="rounded-full bg-emerald-100 w-12 h-12 flex items-center justify-center mb-4 dark:bg-emerald-900">
                                        <svg className="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                        </svg>
                                    </div>
                                    <h3 className="text-lg font-medium text-gray-900 mb-2 dark:text-white">Control de Inventario</h3>
                                    <p className="text-gray-500 dark:text-gray-300">
                                        Seguimiento en tiempo real de tu inventario. Alertas de stock bajo y optimización de mercancía.
                                    </p>
                                </div>

                                {/* Feature 3 */}
                                <div className="bg-gray-50 p-6 rounded-xl shadow-lg transform transition duration-500 hover:scale-105 dark:bg-gray-700">
                                    <div className="rounded-full bg-orange-100 w-12 h-12 flex items-center justify-center mb-4 dark:bg-orange-900">
                                        <svg className="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                    <h3 className="text-lg font-medium text-gray-900 mb-2 dark:text-white">Reportes y Estadísticas</h3>
                                    <p className="text-gray-500 dark:text-gray-300">
                                        Informes detallados y visualización de datos que te ayudarán a tomar mejores decisiones estratégicas.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* CTA Section */}
                <section className="bg-blue-600 dark:bg-blue-800">
                    <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-8 lg:flex lg:items-center lg:justify-between">
                        <h2 className="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                            <span className="block">¿Listo para potenciar tu negocio?</span>
                            <span className="block text-blue-200">Comienza a usar GestVenin hoy mismo.</span>
                        </h2>
                        <div className="mt-8 flex lg:mt-0 lg:flex-shrink-0">
                            <div className="inline-flex rounded-md shadow">
                                {auth.user ? (
                                    <Link
                                        href={route('dashboard')}
                                        className="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-blue-600 bg-white hover:bg-blue-50"
                                    >
                                        Ir al Dashboard
                                    </Link>
                                ) : (
                                    <Link
                                        href={route('login')}
                                        className="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-blue-600 bg-white hover:bg-blue-50"
                                    >
                                        Iniciar Sesión
                                    </Link>
                                )}
                            </div>
                            <div className="ml-3 inline-flex rounded-md shadow">
                                <a
                                    href="tel:+51936313648"
                                    className="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-700 hover:bg-blue-800"
                                >
                                    Contactar
                                </a>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Testimonials Section */}
                <section className="py-16 bg-gray-50 dark:bg-gray-900">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center">
                            <h2 className="text-3xl font-extrabold text-gray-900 dark:text-white">
                                Lo que dicen nuestros clientes
                            </h2>
                            <p className="mt-4 text-lg text-gray-500 dark:text-gray-300">
                                Empresas que han transformado su negocio con GestVenin
                            </p>
                        </div>
                        <div className="mt-12 grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                            {/* Testimonial 1 */}
                            <div className="bg-white p-6 rounded-lg shadow-lg dark:bg-gray-800">
                                <div className="flex items-center mb-4">
                                    <div className="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center dark:bg-blue-900">
                                        <span className="text-xl font-bold text-blue-600 dark:text-blue-400">A</span>
                                    </div>
                                    <div className="ml-4">
                                        <h3 className="text-lg font-medium text-gray-900 dark:text-white">Alejandro López</h3>
                                        <p className="text-gray-500 dark:text-gray-400">Minimarket "El Ahorro"</p>
                                    </div>
                                </div>
                                <p className="text-gray-600 dark:text-gray-300">
                                    "GestVenin ha simplificado completamente nuestras operaciones diarias. El control de inventario es exacto y las ventas nunca han sido tan fáciles de gestionar."
                                </p>
                            </div>

                            {/* Testimonial 2 */}
                            <div className="bg-white p-6 rounded-lg shadow-lg dark:bg-gray-800">
                                <div className="flex items-center mb-4">
                                    <div className="h-12 w-12 rounded-full bg-emerald-100 flex items-center justify-center dark:bg-emerald-900">
                                        <span className="text-xl font-bold text-emerald-600 dark:text-emerald-400">M</span>
                                    </div>
                                    <div className="ml-4">
                                        <h3 className="text-lg font-medium text-gray-900 dark:text-white">María González</h3>
                                        <p className="text-gray-500 dark:text-gray-400">Boutique "Eleganza"</p>
                                    </div>
                                </div>
                                <p className="text-gray-600 dark:text-gray-300">
                                    "Desde que implementamos GestVenin, nuestras ventas aumentaron un 30%. La facilidad para consultar el inventario nos ha permitido mejorar la experiencia del cliente."
                                </p>
                            </div>

                            {/* Testimonial 3 */}
                            <div className="bg-white p-6 rounded-lg shadow-lg dark:bg-gray-800">
                                <div className="flex items-center mb-4">
                                    <div className="h-12 w-12 rounded-full bg-orange-100 flex items-center justify-center dark:bg-orange-900">
                                        <span className="text-xl font-bold text-orange-600 dark:text-orange-400">J</span>
                                    </div>
                                    <div className="ml-4">
                                        <h3 className="text-lg font-medium text-gray-900 dark:text-white">Juan Pérez</h3>
                                        <p className="text-gray-500 dark:text-gray-400">Ferretería "Construye"</p>
                                    </div>
                                </div>
                                <p className="text-gray-600 dark:text-gray-300">
                                    "Los reportes automáticos me han ayudado a tomar mejores decisiones de compra. Con GestVenin tengo mi negocio bajo control desde cualquier lugar."
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="bg-gray-900 text-white">
                    <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                            <div>
                                <div className="flex items-center">
                                    <span className="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-emerald-500">
                                        Gest<span className="text-orange-500">Venin</span>
                                    </span>
                                </div>
                                <p className="mt-2 text-sm text-gray-400">
                                    La solución integral para la gestión de ventas e inventario para todo tipo de negocios.
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm font-semibold text-gray-300 tracking-wider uppercase">Soluciones</h3>
                                <ul className="mt-4 space-y-2">
                                    <li><a href="#" className="text-gray-400 hover:text-white">Ventas</a></li>
                                    <li><a href="#" className="text-gray-400 hover:text-white">Inventario</a></li>
                                    <li><a href="#" className="text-gray-400 hover:text-white">Reportes</a></li>
                                    <li><a href="#" className="text-gray-400 hover:text-white">Clientes</a></li>
                                </ul>
                            </div>
                            <div>
                                <h3 className="text-sm font-semibold text-gray-300 tracking-wider uppercase">Empresa</h3>
                                <ul className="mt-4 space-y-2">
                                    <li><a href="#" className="text-gray-400 hover:text-white">Acerca de</a></li>
                                    <li><a href="#" className="text-gray-400 hover:text-white">Blog</a></li>
                                    <li><a href="#" className="text-gray-400 hover:text-white">Empleos</a></li>
                                    <li><a href="#" className="text-gray-400 hover:text-white">Socios</a></li>
                                </ul>
                            </div>
                            <div>
                                <h3 className="text-sm font-semibold text-gray-300 tracking-wider uppercase">Contáctanos</h3>
                                <ul className="mt-4 space-y-2">
                                    <li className="flex items-center">
                                        <svg className="mr-2 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                        </svg>
                                        <a href="tel:+51936313648" className="text-gray-400 hover:text-white">+51 936 313 648</a>
                                    </li>
                                    <li className="flex items-center">
                                        <svg className="mr-2 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        <a href="mailto:info@gestvenin.com" className="text-gray-400 hover:text-white">info@gestvenin.com</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div className="mt-8 border-t border-gray-800 pt-8 md:flex md:items-center md:justify-between">
                            <div className="flex space-x-6 md:order-2">
                                {/* Social Media Icons */}
                                <a href="#" className="text-gray-400 hover:text-white">
                                    <span className="sr-only">Facebook</span>
                                    <svg className="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path fillRule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clipRule="evenodd" />
                                    </svg>
                                </a>
                                <a href="#" className="text-gray-400 hover:text-white">
                                    <span className="sr-only">Instagram</span>
                                    <svg className="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path fillRule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clipRule="evenodd" />
                                    </svg>
                                </a>
                                <a href="#" className="text-gray-400 hover:text-white">
                                    <span className="sr-only">Twitter</span>
                                    <svg className="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.271 8.207 8.207 0 01-2.605.996A4.107 4.107 0 0015.116 4c-2.27 0-4.115 1.845-4.115 4.115 0 .322.036.637.106.938A11.65 11.65 0 013.16 4.596a4.115 4.115 0 001.273 5.48A4.08 4.08 0 012 .96v.052a4.113 4.113 0 003.293 4.033A4.1 4.1 0 012 .96v-.052a11.64 11.64 0 008.29-2.287A11.65 11.65 0 0112 .587a11.65 11.65 0 01-3 .587z" />
                                    </svg>
                                </a>
                            </div>
                            <p className="mt-8 text-base text-gray-400 md:order-1 md:mt-0">
                                &copy; 2025 GestVenin. Todos los derechos reservados.
                            </p>
                            <div className="mt-8 text-base text-gray-400 md:order-1 md:mt-0">
                                <a href="#" className="hover:text-white">Política de Privacidad</a>
                                <span className="mx-2">|</span>
                                <a href="#" className="hover:text-white">Términos de Servicio</a>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
  