import AppProvider from '@/Providers/HeroUIProvider';   // si tu provider está aquí

export default function AuthLayout({ children }) {
  return (
    <AppProvider>
      {/* pantalla completa, sin logo Laravel */}
      {children}
    </AppProvider>
  );
}
