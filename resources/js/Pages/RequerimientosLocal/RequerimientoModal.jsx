// resources/js/Pages/RequerimientosLocal/RequerimientoModal.jsx
import React, { useState, useEffect } from 'react'
import { useForm } from '@inertiajs/react'
import {
  Modal,
  ModalContent,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Select,
  SelectItem,
  Textarea,
  Divider,
  Table,
  TableHeader,
  TableColumn,
  TableBody,
  TableRow,
  TableCell,
  Input,
  Button,
  Badge,
} from '@heroui/react'
import { Trash, AlertTriangle } from 'lucide-react'
import toast from 'react-hot-toast'

export default function RequerimientoModal({
  isOpen,
  onClose,
  requerimiento,
  locales,
  productos,
  onSaved,
}) {
  if (!requerimiento) return null

  // 1️⃣ Stocks
  const [stockMap, setStockMap] = useState({})

  // 2️⃣ Formulario Inertia
  const { data, setData, put, processing } = useForm({
    local_id:      '',
    observaciones: '',
    detalles:      [],
  })

  // 3️⃣ Cada vez que abras ESTE modal, recarga el form con **estos** datos
  useEffect(() => {
    if (!isOpen) return
    setData({
      local_id:      requerimiento.local_id,
      observaciones: requerimiento.observaciones || '',
      detalles:      (requerimiento.detalles || []).map(d => {
        const prod = productos.find(p => p.id === d.producto_almacen_id)
        return {
          producto_almacen_id: d.producto_almacen_id,
          nombre_producto:     prod?.nombre || '‹sin nombre›',
          cantidad_requerida:  d.cantidad_requerida ?? 1,
          stock_actual:        0, // luego lo sincronizamos
        }
      }),
    })
    setNuevoId('')
    setStockMap({})
  }, [isOpen, requerimiento.id, productos, setData])

  // 4️⃣ Al abrir, traemos el stock real
  useEffect(() => {
    if (!isOpen) return
    fetch(`/api/inventario-local/${requerimiento.local_id}`)
      .then(r => r.json())
      .then(list => {
        const mapa = list.reduce((m, x) => {
          m[x.producto_almacen_id] = x.cantidad
          return m
        }, {})
        setStockMap(mapa)
      })
      .catch(() => toast.error('No fue posible cargar el stock'))
  }, [isOpen, requerimiento.local_id])

  // 5️⃣ Cuando cambia stockMap, sincronizamos
  useEffect(() => {
    if (!isOpen) return
    setData('detalles', data.detalles.map(d => ({
      ...d,
      stock_actual: stockMap[d.producto_almacen_id] ?? 0,
    })))
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [stockMap])

  // 6️⃣ Nuevo producto a añadir
  const [nuevoId, setNuevoId] = useState('')

  function addProd() {
    if (!nuevoId) {
      return toast.error('Selecciona un producto')
    }
    if (data.detalles.some(d => String(d.producto_almacen_id) === nuevoId)) {
      return toast.error('Ya está en la lista')
    }
    const p = productos.find(x => String(x.id) === nuevoId)
    setData('detalles', [
      ...data.detalles,
      {
        producto_almacen_id: p.id,
        nombre_producto:     p.nombre,
        cantidad_requerida:  1,
        stock_actual:        stockMap[p.id] ?? 0,
      },
    ])
    setNuevoId('')
  }

  function delProd(i) {
    setData('detalles', data.detalles.filter((_, idx) => idx !== i))
  }

  // 7️⃣ Enviar
  function submit(e) {
    e.preventDefault()
    if (!data.detalles.length) {
      return toast.error('Agrega al menos un producto')
    }
    const url = route('requerimientos_local.update', {
      requerimiento: requerimiento.id,
    })
    put(url, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Requerimiento actualizado')
        onSaved()
      },
      onError: () => toast.error('Corrige los errores'),
    })
  }

  return (
    <Modal isOpen={isOpen} onClose={onClose} size="3xl">
      <ModalContent>
        {closeFn => (
          <>
            <ModalHeader>Editar Req. #{requerimiento.id}</ModalHeader>
            <ModalBody className="space-y-6">
              <form id="form-editar-req" onSubmit={submit} className="space-y-6">
                {/* Local */}
                <Input
                  label="Local"
                  isDisabled
                  defaultValue={requerimiento.local?.nombre_local || '—'}
                />
                <input type="hidden" name="local_id" value={data.local_id} />

                {/* Observaciones */}
                <Textarea
                  label="Observaciones"
                  value={data.observaciones}
                  onChange={e => setData('observaciones', e.target.value)}
                />

                <Divider />

                {/* Añadir producto */}
                <div className="flex gap-2 items-end">
                  <Select
                    label="Agregar producto"
                    placeholder="Selecciona..."
                    searchable
                    filterable
                    value={nuevoId}
                    onChange={e => setNuevoId(e.target.value)}
                    className="flex-1"
                  >
                    {productos.map(p => (
                      <SelectItem
                        key={p.id}
                        value={String(p.id)}
                        textValue={p.nombre}
                      >
                        {p.nombre}
                      </SelectItem>
                    ))}
                  </Select>
                  <Button color="primary" onPress={addProd}>
                    Añadir
                  </Button>
                </div>

                {/* Tabla de detalles */}
                {data.detalles.length ? (
                  <Table aria-label="Detalles del requerimiento">
                    <TableHeader>
                      <TableColumn>Producto</TableColumn>
                      <TableColumn>Stock</TableColumn>
                      <TableColumn width={80}>Eliminar</TableColumn>
                    </TableHeader>
                    <TableBody>
                      {data.detalles.map((d,i) => (
                        <TableRow key={`${d.producto_almacen_id}-${i}`}>
                          <TableCell>{d.nombre_producto}</TableCell>
                          <TableCell>
                            <Badge
                              variant="flat"
                              color={d.stock_actual > 5 ? 'success' : 'warning'}
                            >
                              {d.stock_actual}
                            </Badge>
                          </TableCell>
                          <TableCell>
                            <Button
                              isIconOnly
                              color="danger"
                              size="sm"
                              onPress={() => delProd(i)}
                            >
                              <Trash className="h-4 w-4" />
                            </Button>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                ) : (
                  <div className="p-4 bg-yellow-50 flex items-center gap-2">
                    <AlertTriangle className="h-5 w-5 text-yellow-700" />
                    <span>No hay productos agregados.</span>
                  </div>
                )}
              </form>
            </ModalBody>
            <ModalFooter className="justify-between">
              <Button variant="flat" color="danger" onPress={closeFn}>
                Cancelar
              </Button>
              <Button
                type="submit"
                form="form-editar-req"
                color="primary"
                isDisabled={processing}
              >
                Guardar
              </Button>
            </ModalFooter>
          </>
        )}
      </ModalContent>
    </Modal>
  )
}
