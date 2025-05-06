// resources/js/Components/DataTable.jsx
import React, { useState, useMemo } from 'react';

export default function DataTable({ columns, data: initialData }) {
  const [searchTerm, setSearchTerm] = useState('');
  const [rowsPerPage, setRowsPerPage] = useState(10);
  const [currentPage, setCurrentPage] = useState(1);
  const [sortConfig, setSortConfig] = useState({ key: null, direction: 'asc' });

  // Utilidad para acceder a campos anidados: "local.nombre_local"
  const getValue = (obj, path) =>
    path.split('.').reduce((acc, key) => (acc != null ? acc[key] : null), obj);

  // ── 1) FILTRADO GLOBAL ──────────────────────────────────────────────────────
  const filteredData = useMemo(() => {
    if (!searchTerm) return initialData;
    const lower = searchTerm.toLowerCase();
    return initialData.filter(row =>
      // JSON.stringify busca en todo el objeto
      JSON.stringify(row).toLowerCase().includes(lower)
    );
  }, [initialData, searchTerm]);

  // ── 2) ORDENAMIENTO ─────────────────────────────────────────────────────────
  const sortedData = useMemo(() => {
    if (!sortConfig.key) return filteredData;
    const { key, direction } = sortConfig;
    return [...filteredData].sort((a, b) => {
      const aVal = getValue(a, key);
      const bVal = getValue(b, key);
      if (aVal == null) return 1;
      if (bVal == null) return -1;
      let cmp = 0;
      if (typeof aVal === 'string') {
        cmp = aVal.localeCompare(bVal, undefined, { sensitivity: 'base' });
      } else {
        cmp = aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
      }
      return direction === 'asc' ? cmp : -cmp;
    });
  }, [filteredData, sortConfig]);

  // ── 3) PAGINACIÓN ──────────────────────────────────────────────────────────
  const totalPages = Math.max(1, Math.ceil(sortedData.length / rowsPerPage));
  const paginatedData = useMemo(() => {
    const start = (currentPage - 1) * rowsPerPage;
    return sortedData.slice(start, start + rowsPerPage);
  }, [sortedData, currentPage, rowsPerPage]);

  // Genera un array de páginas (máx 5 visibles)
  const pageNumbers = useMemo(() => {
    const pages = [];
    const max = 5;
    if (totalPages <= max) {
      for (let i = 1; i <= totalPages; i++) pages.push(i);
    } else if (currentPage <= 3) {
      pages.push(1, 2, 3, 4, 5);
    } else if (currentPage >= totalPages - 2) {
      for (let i = totalPages - 4; i <= totalPages; i++) pages.push(i);
    } else {
      for (let i = currentPage - 2; i <= currentPage + 2; i++) pages.push(i);
    }
    return pages;
  }, [totalPages, currentPage]);

  // Cambiar criterio de orden
  const handleSort = (field) => {
    if (!field) return;
    let direction = 'asc';
    if (sortConfig.key === field && sortConfig.direction === 'asc') {
      direction = 'desc';
    }
    setSortConfig({ key: field, direction });
  };

  return (
    <div className="space-y-6">
      {/* ─── 1) BÚSQUEDA & FILAS POR PÁGINA ─────────────────────────────────── */}
      <div className="bg-white p-4 rounded-lg shadow flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div className="relative w-full md:w-1/3">
          <input
            type="text"
            placeholder="Buscar..."
            className="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring focus:border-blue-300"
            value={searchTerm}
            onChange={e => {
              setSearchTerm(e.target.value);
              setCurrentPage(1);
            }}
          />
          <span className="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
            🔍
          </span>
        </div>

        <div className="flex items-center gap-2">
          <label className="text-sm text-gray-700">Filas por página:</label>
          <select
            className="border border-gray-300 rounded-lg p-2"
            value={rowsPerPage}
            onChange={e => {
              setRowsPerPage(Number(e.target.value));
              setCurrentPage(1);
            }}
          >
            {[5, 10, 25, 50].map(n => (
              <option key={n} value={n}>{n}</option>
            ))}
          </select>
        </div>
      </div>

      {/* ─── 2) TABLA ───────────────────────────────────────────────────────── */}
      <div className="overflow-x-auto bg-white rounded-lg shadow border">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              {columns.map((col, idx) => (
                <th
                  key={col.field || idx}
                  className={`px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider ${
                    col.field ? 'cursor-pointer hover:bg-gray-100' : ''
                  }`}
                  onClick={() => handleSort(col.field)}
                >
                  <div className="flex items-center space-x-1">
                    <span>{col.label}</span>
                    {sortConfig.key === col.field && (
                      <span>{sortConfig.direction === 'asc' ? '↑' : '↓'}</span>
                    )}
                  </div>
                </th>
              ))}
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {paginatedData.length > 0 ? (
              paginatedData.map((row, rIdx) => (
                <tr key={row.id ?? rIdx} className="hover:bg-gray-50">
                  {columns.map((col, cIdx) => (
                    <td
                      key={`${rIdx}-${cIdx}`}
                      className="px-6 py-4 text-sm text-gray-600"
                    >
                      {col.render
                        ? col.render(row)
                        : col.field
                        ? getValue(row, col.field)
                        : ''}
                    </td>
                  ))}
                </tr>
              ))
            ) : (
              <tr>
                <td
                  colSpan={columns.length}
                  className="px-6 py-12 text-center text-gray-500"
                >
                  No se encontraron resultados
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      {/* ─── 3) PIE DE PAGINACIÓN ──────────────────────────────────────────── */}
      <div className="bg-white p-4 rounded-lg shadow flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div className="text-sm text-gray-700">
          Mostrando{' '}
          <strong>
            {paginatedData.length === 0
              ? 0
              : (currentPage - 1) * rowsPerPage + 1}
          </strong>{' '}
          a{' '}
          <strong>
            {Math.min(
              (currentPage - 1) * rowsPerPage + paginatedData.length,
              filteredData.length
            )}
          </strong>{' '}
          de <strong>{filteredData.length}</strong> registros
        </div>

        <div className="inline-flex items-center space-x-1">
          <button
            onClick={() => setCurrentPage(1)}
            disabled={currentPage === 1}
            className="px-3 py-1 rounded disabled:opacity-50"
          >
            «
          </button>
          <button
            onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
            disabled={currentPage === 1}
            className="px-3 py-1 rounded disabled:opacity-50"
          >
            ‹
          </button>

          {pageNumbers.map(n => (
            <button
              key={n}
              onClick={() => setCurrentPage(n)}
              className={`px-3 py-1 rounded ${
                currentPage === n
                  ? 'bg-blue-600 text-white'
                  : 'bg-white text-gray-700 hover:bg-gray-100'
              }`}
            >
              {n}
            </button>
          ))}

          <button
            onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))}
            disabled={currentPage === totalPages}
            className="px-3 py-1 rounded disabled:opacity-50"
          >
            ›
          </button>
          <button
            onClick={() => setCurrentPage(totalPages)}
            disabled={currentPage === totalPages}
            className="px-3 py-1 rounded disabled:opacity-50"
          >
            »
          </button>
        </div>
      </div>
    </div>
  );
}
