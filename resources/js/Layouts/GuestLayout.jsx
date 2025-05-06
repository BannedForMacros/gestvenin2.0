/* resources/js/Layouts/GuestLayout.jsx */

import React from 'react';

export default function GuestLayout({ children }) {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-100 p-4">
      {/* Contenedor del contenido público (login, register, etc.) */}
      <div
        className="w-full max-w-md lg:max-w-lg bg-white shadow-md rounded-lg
                   px-8 py-10 lg:px-10"
      >
        {children}
      </div>
    </div>
  );
}
