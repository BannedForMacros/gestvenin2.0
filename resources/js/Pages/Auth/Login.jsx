import React, { useState, useEffect, useRef } from 'react';
import { Head, useForm, Link, router } from '@inertiajs/react';
import { HeroUIProvider, Card, CardHeader, CardBody, Button, Input, Checkbox } from '@heroui/react';
import { 
  PlusIcon, 
  EnvelopeIcon, 
  LockClosedIcon, 
  EyeIcon, 
  EyeSlashIcon,
  ArrowRightIcon
} from '@heroicons/react/24/outline';

// Paleta de colores mejorada
const COLORS = {
  primary: '#0066cc',     // Azul principal más vibrante
  secondary: '#ff7e33',   // Naranja secundario ajustado
  accent: '#4caf50',      // Verde acento
  darkBlue: '#003366',    // Azul oscuro para contraste
  background: '#0a192f',  // Fondo azul oscuro para estilo tech/futurista
  backgroundLight: '#f7f9fc', // Fondo claro para tarjeta
  text: '#374151',        // Gris oscuro para texto
  textLight: '#94a3b8',   // Gris claro para texto menos importante
  highlight: '#60a5fa',   // Azul claro para resaltes
  border: 'rgba(99, 179, 237, 0.4)',   // Borde con brillo suave
  cardGlow: '0 0 20px rgba(96, 165, 250, 0.3)'  // Brillo suave para tarjeta
};

// Componente de animación de partículas
function ParticleAnimation() {
  const canvasRef = useRef(null);
  
  useEffect(() => {
    const canvas = canvasRef.current;
    const ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    
    // Ajustar el canvas si la ventana cambia de tamaño
    const handleResize = () => {
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;
    };
    window.addEventListener('resize', handleResize);
    
    // Clase para crear partículas
    class Particle {
      constructor() {
        this.x = Math.random() * canvas.width;
        this.y = Math.random() * canvas.height;
        this.size = Math.random() * 5 + 1;
        this.speedX = Math.random() * 2 - 1;
        this.speedY = Math.random() * 2 - 1;
        this.color = this.getRandomColor();
      }
      
      getRandomColor() {
        const colors = [
          'rgba(0, 102, 204, 0.4)',    // Primary
          'rgba(255, 126, 51, 0.3)',   // Secondary
          'rgba(76, 175, 80, 0.3)',    // Accent
          'rgba(96, 165, 250, 0.5)',   // Highlight
        ];
        return colors[Math.floor(Math.random() * colors.length)];
      }
      
      update() {
        this.x += this.speedX;
        this.y += this.speedY;
        
        // Rebote en los bordes
        if (this.x > canvas.width || this.x < 0) {
          this.speedX = -this.speedX;
        }
        if (this.y > canvas.height || this.y < 0) {
          this.speedY = -this.speedY;
        }
      }
      
      draw() {
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
        ctx.fillStyle = this.color;
        ctx.fill();
      }
    }
    
    // Crear arreglo de partículas
    const particleArray = [];
    const particleCount = Math.min(100, (canvas.width * canvas.height) / 15000);
    
    for (let i = 0; i < particleCount; i++) {
      particleArray.push(new Particle());
    }
    
    // Función para dibujar conexiones entre partículas cercanas
    function connect() {
      for (let a = 0; a < particleArray.length; a++) {
        for (let b = a; b < particleArray.length; b++) {
          const dx = particleArray[a].x - particleArray[b].x;
          const dy = particleArray[a].y - particleArray[b].y;
          const distance = Math.sqrt(dx * dx + dy * dy);
          
          if (distance < 100) {
            const opacity = 1 - (distance / 100);
            ctx.strokeStyle = `rgba(96, 165, 250, ${opacity * 0.3})`;
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(particleArray[a].x, particleArray[a].y);
            ctx.lineTo(particleArray[b].x, particleArray[b].y);
            ctx.stroke();
          }
        }
      }
    }
    
    // Función de animación
    function animate() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      
      for (let i = 0; i < particleArray.length; i++) {
        particleArray[i].update();
        particleArray[i].draw();
      }
      connect();
      requestAnimationFrame(animate);
    }
    
    animate();
    
    return () => {
      window.removeEventListener('resize', handleResize);
    };
  }, []);
  
  return (
    <canvas 
      ref={canvasRef} 
      className="absolute top-0 left-0 w-full h-full -z-10"
    />
  );
}

function Provider({ children }) {
  const navigate = (href, opts = {}) =>
    router.visit(typeof href === 'string' ? href : String(href), opts);
  const useHref = (href) => (typeof href === 'string' ? href : String(href));

  return (
    <HeroUIProvider navigate={navigate} useHref={useHref} locale="es-PE">
      {children}
    </HeroUIProvider>
  );
}

export default function Login() {
  const { data, setData, post, processing, errors, reset } = useForm({
    email: '',
    password: '',
    remember: false,
  });
  const [showPwd, setShowPwd] = useState(false);
  const [isLoading, setIsLoading] = useState(true);

  // Efecto de carga futurista
  useEffect(() => {
    const timer = setTimeout(() => {
      setIsLoading(false);
    }, 1000);
    
    return () => clearTimeout(timer);
  }, []);

  const submit = (e) => {
    e.preventDefault();
    post(route('login'), { onFinish: () => reset('password') });
  };

  // Componente de spinner para carga
  const LoadingSpinner = () => (
    <div className="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-90">
      <div className="relative">
        <div className="w-16 h-16 border-4 border-t-transparent border-blue-400 rounded-full animate-spin"></div>
        <div className="w-16 h-16 border-4 border-t-transparent border-orange-400 rounded-full animate-spin absolute top-0 left-0" style={{ animationDelay: '0.3s' }}></div>
        <div className="absolute top-20 text-blue-400 text-lg font-bold">GestVenin</div>
      </div>
    </div>
  );

  return (
    <Provider>
      <Head title="Iniciar Sesión – GestVenin" />
      
      {/* Fondo animado */}
      <div 
        className="min-h-screen flex items-center justify-center overflow-hidden relative py-10"
        style={{ backgroundColor: COLORS.background }}
      >
        <ParticleAnimation />
        
        {/* Efecto de gradiente */}
        <div className="absolute inset-0 bg-gradient-to-br from-transparent via-transparent to-black opacity-30 -z-10"></div>
        
        {isLoading && <LoadingSpinner />}
        
        <div className="w-full max-w-md px-4 z-10">
          {/* Logo futurista */}
          <div className="flex justify-center mb-8 relative">
            <div className="h-20 w-20 rounded-xl flex items-center justify-center relative overflow-hidden"
                style={{ 
                  backgroundColor: COLORS.darkBlue,
                  boxShadow: `0 0 30px ${COLORS.highlight}`
                }}>
              <div className="absolute inset-0 opacity-20">
                <div className="absolute top-0 right-0 w-16 h-6 bg-blue-400 rounded-full blur-xl"></div>
                <div className="absolute bottom-0 left-0 w-16 h-6 bg-orange-400 rounded-full blur-xl"></div>
              </div>
              <span className="text-white text-3xl font-bold" style={{ fontFamily: 'monospace' }}>GV</span>
            </div>
            <div className="absolute -bottom-2 text-xs font-light text-blue-300">SISTEMA DE GESTIÓN</div>
          </div>
          
          {/* Card con efecto de cristal */}
          <Card className="backdrop-blur-sm border border-opacity-30 shadow-xl rounded-2xl overflow-hidden animate-fadeIn"
               style={{ 
                 background: 'rgba(255, 255, 255, 0.9)',
                 borderColor: COLORS.border,
                 boxShadow: COLORS.cardGlow
               }}>
                <CardHeader className="flex flex-col items-center p-6 pb-4 border-b border-blue-100">
                <h1
                    className="text-4xl font-extrabold"
                    style={{ color: COLORS.primary }}
                >
                    ¡Bienvenido!
                </h1>
                <p
                    className="mt-2 text-base text-center"
                    style={{ color: COLORS.text }}
                >
                    Inicia sesión en{' '}
                    <span className="font-semibold" style={{ color: COLORS.primary }}>
                    GestVenin
                    </span>{' '}
                    para continuar
                </p>
                </CardHeader>


            <CardBody as="form" onSubmit={submit} className="space-y-6 p-6">
              {/* Correo Electrónico */}
              <Input
                label="Correo Electrónico"
                type="email"
                variant="bordered"
                color="primary"
                fullWidth
                size="lg"
                radius="lg"
                value={data.email}
                onValueChange={(val) => setData('email', val)}
                isInvalid={!!errors.email}
                errorMessage={errors.email}
                startContent={<EnvelopeIcon className="h-5 w-5 text-blue-500" />}
                className="bg-blue-50 bg-opacity-50"
              />

              {/* Contraseña */}
              <Input
                label="Contraseña"
                type={showPwd ? 'text' : 'password'}
                variant="bordered"
                color="primary"
                fullWidth
                size="lg"
                radius="lg"
                value={data.password}
                onValueChange={(val) => setData('password', val)}
                isInvalid={!!errors.password}
                errorMessage={errors.password}
                startContent={<LockClosedIcon className="h-5 w-5 text-blue-500" />}
                endContent={
                  <button 
                    type="button" 
                    onClick={() => setShowPwd(!showPwd)} 
                    className="focus:outline-none hover:text-blue-600 transition-colors"
                  >
                    {showPwd ? 
                      <EyeSlashIcon className="h-5 w-5 text-blue-500" /> : 
                      <EyeIcon className="h-5 w-5 text-blue-500" />
                    }
                  </button>
                }
                className="bg-blue-50 bg-opacity-50"
              />

              {/* Opciones adicionales */}
              <div className="flex items-center justify-between">
                <Checkbox 
                  color="primary" 
                  size="sm"
                  isSelected={data.remember}
                  onValueChange={(val) => setData('remember', val)}
                >
                  <span className="text-sm" style={{ color: COLORS.text }}>Recordarme</span>
                </Checkbox>
                
                <Link
                  href={route('password.request')}
                  className="text-sm font-medium hover:underline transition-all"
                  style={{ color: COLORS.primary }}
                >
                  ¿Olvidaste tu contraseña?
                </Link>
              </div>

              {/* Botón de inicio de sesión con efecto */}
              <Button
                type="submit"
                disabled={processing}
                fullWidth
                size="lg"
                radius="lg"
                className="font-semibold text-white rounded-lg transition-all group relative overflow-hidden"
                style={{ 
                  background: `linear-gradient(to right, ${COLORS.primary}, ${COLORS.highlight})`,
                  boxShadow: '0 4px 15px rgba(0, 102, 204, 0.3)'
                }}
              >
                <span className="relative z-10 flex items-center justify-center gap-2">
                  {processing ? 'Ingresando...' : 'Iniciar Sesión'}
                  <ArrowRightIcon className="h-5 w-5 transform group-hover:translate-x-1 transition-transform" />
                </span>
                <span className="absolute inset-0 bg-gradient-to-r from-blue-600 to-blue-500 opacity-0 group-hover:opacity-100 transition-opacity"></span>
              </Button>

              {/* Enlace de registro con efecto */}
              <div className="text-center pt-4">
                <p style={{ color: COLORS.text }}>
                  ¿No tienes cuenta?{' '}
                  <Link
                    href={route('register')}
                    className="inline-flex items-center gap-1 font-medium transition-all relative group"
                    style={{ color: COLORS.secondary }}
                  >
                    <span className="group-hover:translate-x-1 transition-transform inline-flex items-center">
                      <PlusIcon className="h-4 w-4" /> Registrarse
                    </span>
                    <span className="absolute bottom-0.5 left-0 right-0 h-0.5 bg-orange-400 scale-x-0 group-hover:scale-x-100 transition-transform origin-left"></span>
                  </Link>
                </p>
              </div>
            </CardBody>
          </Card>
          
          {/* Pie de página con efecto */}
          <div className="text-center mt-8 text-xs" style={{ color: 'rgba(255, 255, 255, 0.7)' }}>
            <p>© {new Date().getFullYear()} GestVenin by MacSoft. Todos los derechos reservados.</p>
            <div className="flex items-center justify-center gap-3 mt-2">
              <a href="#" className="text-blue-300 hover:text-white transition-colors">Términos</a>
              <span className="text-gray-500">•</span>
              <a href="#" className="text-blue-300 hover:text-white transition-colors">Privacidad</a>
              <span className="text-gray-500">•</span>
              <a href="#" className="text-blue-300 hover:text-white transition-colors">Soporte</a>
            </div>
          </div>
        </div>
      </div>
    </Provider>
  );
}

// Estilos globales necesarios para las animaciones
document.head.insertAdjacentHTML(
  'beforeend',
  `<style>
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeIn {
      animation: fadeIn 0.5s ease-out forwards;
    }
    body {
      margin: 0;
      overflow-x: hidden;
    }
  </style>`
);