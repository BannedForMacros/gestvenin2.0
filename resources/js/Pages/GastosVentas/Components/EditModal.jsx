// resources/js/Pages/GastosVentas/Components/EditModal.jsx
import React, { useState, useEffect } from 'react'
import { router } from '@inertiajs/react'
import {
  Modal,
  ModalContent,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Button,
  Input,
  Select,
  SelectItem,
  Chip,
} from '@heroui/react'
import { CalendarIcon, CheckIcon } from '@heroicons/react/24/outline'

export default function EditModal({
  isOpen,
  onClose,
  gasto,
  locales,
  tiposGastos,
  clasificacionesGastos,
  auth
}) {
  const isAdmin = auth.roles.includes('admin') || auth.roles.includes('dueño')

  const [form, setForm] = useState({
    fecha_gasto: '',
    tipo_gasto_id: '',
    clasificacion_gasto_id: '',
    descripcion: '',
    comprobante_de_pago: '',
    monto: '',
    local_id: ''
  })
  const [errors, setErrors] = useState({})

  // Cuando cargo el gasto, inicializo el form:
  useEffect(() => {
    if (!gasto) return
    setForm({
      fecha_gasto: gasto.fecha_gasto || '',
      tipo_gasto_id: String(gasto.tipo_gasto_id || ''),
      clasificacion_gasto_id: String(gasto.clasificacion_gasto_id || ''),
      descripcion: gasto.descripcion || '',
      comprobante_de_pago: gasto.comprobante_de_pago || '',
      monto: String(gasto.monto ?? ''),
      local_id: String(gasto.local_id || '')
    })
    setErrors({})
  }, [gasto])

  const handleChange = (field, value) => {
    setForm(f => ({ ...f, [field]: value }))
    setErrors(e => { const c = { ...e }; delete c[field]; return c })
  }

  const handleSubmit = () => {
    router.put(
      `/gastos_ventas/${gasto.id}`,
      form,
      {
        onError: errs => setErrors(errs),
        onSuccess: () => onClose()
      }
    )
  }

  if (!gasto) return null

  return (
    <Modal isOpen={isOpen} onClose={onClose} width="600px">
      <ModalContent className="p-4">
        <ModalHeader>Editar Gasto</ModalHeader>
        <ModalBody className="space-y-4">
          {/* Fecha */}
          <div>
            <label className="block text-sm">Fecha</label>
            <div className="flex items-center gap-2">
              <CalendarIcon className="h-5 w-5 text-gray-400" />
              <Input
                type="date"
                value={form.fecha_gasto}
                onChange={e => handleChange('fecha_gasto', e.target.value)}
                className="w-full"
              />
            </div>
            {errors.fecha_gasto && (
              <p className="text-red-600 text-xs">{errors.fecha_gasto}</p>
            )}
          </div>

          {/* Tipo */}
          <div>
            <label className="block text-sm">Tipo</label>
            <Select
              placeholder="Seleccione tipo"
              selectedKeys={
                form.tipo_gasto_id ? [form.tipo_gasto_id] : []
              }
              onChange={e => handleChange('tipo_gasto_id', e.target.value)}
            >
              {tiposGastos.map(t => (
                <SelectItem key={t.id} value={String(t.id)}>
                  {t.nombre}
                </SelectItem>
              ))}
            </Select>
            {errors.tipo_gasto_id && (
              <p className="text-red-600 text-xs">{errors.tipo_gasto_id}</p>
            )}
          </div>

          {/* Clasificación */}
          <div>
            <label className="block text-sm">Clasificación</label>
            <Select
              placeholder="Seleccione clasificación"
              selectedKeys={
                form.clasificacion_gasto_id
                  ? [form.clasificacion_gasto_id]
                  : []
              }
              onChange={e =>
                handleChange('clasificacion_gasto_id', e.target.value)
              }
            >
              {clasificacionesGastos
                .filter(c => String(c.tipo_gasto_id) === form.tipo_gasto_id)
                .map(c => (
                  <SelectItem key={c.id} value={String(c.id)}>
                    {c.nombre}
                  </SelectItem>
                ))}
            </Select>
            {errors.clasificacion_gasto_id && (
              <p className="text-red-600 text-xs">
                {errors.clasificacion_gasto_id}
              </p>
            )}
          </div>

          {/* Descripción */}
          <div>
            <label className="block text-sm">Descripción</label>
            <Input
              value={form.descripcion}
              onChange={e => handleChange('descripcion', e.target.value)}
            />
          </div>

          {/* Comprobante */}
          <div>
            <label className="block text-sm">Comprobante</label>
            <Input
              value={form.comprobante_de_pago}
              onChange={e =>
                handleChange('comprobante_de_pago', e.target.value)
              }
            />
          </div>

          {/* Monto */}
          <div>
            <label className="block text-sm">Monto</label>
            <Input
              type="number"
              step="0.01"
              value={form.monto}
              onChange={e => handleChange('monto', e.target.value)}
            />
            {errors.monto && (
              <p className="text-red-600 text-xs">{errors.monto}</p>
            )}
          </div>

          {/* Local (solo admin) */}
          {isAdmin && (
            <div>
              <label className="block text-sm">Local</label>
              <Select
                placeholder="Seleccione local"
                selectedKeys={form.local_id ? [form.local_id] : []}
                onChange={e => handleChange('local_id', e.target.value)}
              >
                {locales.map(l => (
                  <SelectItem key={l.id} value={String(l.id)}>
                    {l.nombre_local}
                  </SelectItem>
                ))}
              </Select>
              {errors.local_id && (
                <p className="text-red-600 text-xs">{errors.local_id}</p>
              )}
            </div>
          )}
        </ModalBody>

        <ModalFooter className="flex justify-end gap-2">
          <Button variant="flat" onPress={onClose}>
            Cancelar
          </Button>
          <Button
            color="success"
            startContent={<CheckIcon className="h-4 w-4" />}
            onPress={handleSubmit}
          >
            Guardar
          </Button>
        </ModalFooter>
      </ModalContent>
    </Modal>
  )
}
