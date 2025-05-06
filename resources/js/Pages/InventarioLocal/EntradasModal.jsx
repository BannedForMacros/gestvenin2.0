// resources/js/Pages/InventarioLocal/EntradasModal.jsx
import React, { useState, useEffect } from 'react'
import {
  Modal,
  ModalContent,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Button,
  Spinner,
  Table,
  TableHeader,
  TableColumn,
  TableBody,
  TableRow,
  TableCell,
  useDisclosure,
} from '@heroui/react'
import axios from 'axios'

export default function EntradasModal({
  isOpen: externalOpen,
  onClose: externalClose,
  localId,
  fecha,
}) {
  const { isOpen, onOpen, onClose } = useDisclosure({
    defaultIsOpen: false,
    onClose: () => externalClose(),
  })
  useEffect(() => {
    externalOpen ? onOpen() : onClose()
  }, [externalOpen, onOpen, onClose])

  const [entradas, setEntradas] = useState([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)

  useEffect(() => {
    if (isOpen && localId && fecha) {
      setLoading(true)
      setError(null)
      axios
        .get('/entradas_local/por_fecha', {
          params: { local_id: localId, fecha },
        })
        .then((resp) => setEntradas(resp.data))
        .catch(() => setError('No se pudieron cargar las entradas.'))
        .finally(() => setLoading(false))
    }
  }, [isOpen, localId, fecha])

  function formatDate(isoString) {
    const datePart = isoString.split("T")[0]   // "2025-01-20"
    const [yyyy, mm, dd] = datePart.split("-")
    return `${dd}/${mm}/${yyyy}`
  }

  function formatDateTime(isoString) {
    const d = new Date(isoString) // aquí sí nos importa hora
    return d.toLocaleString('es-ES', {
      day: '2-digit', month: '2-digit', year: 'numeric',
      hour: '2-digit', minute: '2-digit'
    })
  }

  // obtenemos la fecha real de las entradas (si hay alguna), o usamos la prop
  const headerDate =
    entradas.length > 0
      ? formatDate(entradas[0].fecha_entrada)
      : formatDate(fecha)

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      size="2xl"
      scrollBehavior="inside"
      backdrop="opaque"
      motionProps={{
        variants: {
          enter: { y: 0, opacity: 1, transition: { duration: 0.3, ease: 'easeOut' } },
          exit:  { y: -20, opacity: 0, transition: { duration: 0.2, ease: 'easeIn' } },
        },
      }}
    >
      <ModalContent>
        {(close) => (
          <>
            <ModalHeader className="flex justify-between items-center">
              <h3 className="text-lg font-semibold">
                Entradas del {headerDate}
              </h3>
              <Button variant="light" onPress={close}>
                Cerrar
              </Button>
            </ModalHeader>

            <ModalBody>
              {loading ? (
                <div className="flex items-center justify-center py-8 gap-2">
                  <Spinner size="lg" />
                  <span>Cargando…</span>
                </div>
              ) : error ? (
                <p className="text-red-600 text-center py-8">{error}</p>
              ) : entradas.length === 0 ? (
                <p className="text-gray-500 text-center py-8">
                  No hay entradas para esta fecha.
                </p>
              ) : (
                entradas.map((e) => (
                  <div key={e.id} className="mb-6">
                    <div className="flex justify-between mb-2">
                      <strong>Entrada #{e.id}</strong>
                      <span className="text-sm text-gray-600">
                        {formatDateTime(e.fecha_entrada)}
                      </span>
                    </div>
                    <Table aria-label="Detalle de entrada">
                      <TableHeader
                        columns={[
                          { name: 'Producto',   uid: 'prod' },
                          { name: 'Cantidad',   uid: 'cant' },
                          { name: 'P. Unitario',uid: 'pu'   },
                          { name: 'P. Total',   uid: 'pt'   },
                        ]}
                      >
                        {(col) => <TableColumn key={col.uid}>{col.name}</TableColumn>}
                      </TableHeader>
                      <TableBody>
                        {e.detalles.map((det) => {
                          const prod = det.producto_almacen || {}
                          const um   = prod.unidad_medida   || {}
                          const pu   = Number(det.precio_unitario)||0
                          const pt   = Number(det.precio_total)  ||0
                          return (
                            <TableRow key={det.id}>
                              <TableCell>{prod.nombre || '–'}</TableCell>
                              <TableCell>
                                {det.cantidad_entrada} {um.nombre||''}
                              </TableCell>
                              <TableCell>S/ {pu.toFixed(2)}</TableCell>
                              <TableCell>S/ {pt.toFixed(2)}</TableCell>
                            </TableRow>
                          )
                        })}
                      </TableBody>
                    </Table>
                  </div>
                ))
              )}
            </ModalBody>

            <ModalFooter className="flex justify-end gap-2">
              <Button variant="light" onPress={close}>
                Cerrar
              </Button>
            </ModalFooter>
          </>
        )}
      </ModalContent>
    </Modal>
  )
}
