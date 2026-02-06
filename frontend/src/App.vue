<template>
  <div class="container py-4">
    <h3 class="mb-4">Desafio Datafrete</h3>

    <div class="row">
      <div class="col-lg-6 mb-4">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title mb-3">Calcular distância e salvar</h5>

            <div class="form-group">
              <label>CEP origem</label>
              <input v-model="form.cep_origin" class="form-control" placeholder="01001000" />
            </div>

            <div class="form-group">
              <label>CEP destino</label>
              <input v-model="form.cep_destination" class="form-control" placeholder="01311000" />
            </div>

            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" id="modeRoad" v-model="modeRoad">
              <label class="form-check-label" for="modeRoad">
                Calcular por estrada
              </label>
            </div>

            <button class="btn btn-primary" :disabled="saving" @click="createDistance">
              {{ saving ? 'Salvando...' : 'Salvar' }}
            </button>

            <div v-if="formError" class="alert alert-danger mt-3 mb-0">
              {{ formError }}
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6 mb-4">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title mb-3">Importar CSV</h5>

            <input type="file" class="form-control-file" @change="onFileChange" />

            <button class="btn btn-success mt-3" :disabled="!file || importing" @click="uploadCsv">
              {{ importing ? 'Enviando...' : 'Enviar CSV' }}
            </button>

            <div v-if="importError" class="alert alert-danger mt-3 mb-0">
              {{ importError }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-2">
      <h5 class="mb-0">Distâncias</h5>
    </div>

    <div class="card mb-4">
      <div class="table-responsive">
        <table class="table table-striped mb-0">
          <thead>
            <tr>
              <th>ID</th>
              <th>Origem</th>
              <th>Destino</th>
              <th>KM</th>
              <th>KM (estrada)</th>
              <th>Criado em</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="d in distances" :key="d.id">
              <td>{{ d.id }}</td>
              <td>{{ formatCep(d.cep_origin) }}</td>
              <td>{{ formatCep(d.cep_destination) }}</td>
              <td>{{ formatKm(d.distance_km) }}</td>
              <td>{{ d.road_distance_km ? formatKm(d.road_distance_km) : '-' }}</td>
              <td>{{ d.created_at }}</td>
            </tr>
            <tr v-if="distances.length === 0">
              <td colspan="5" class="text-center py-4">Nenhum registro</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-2">
      <h5 class="mb-0">Importações</h5>
    </div>

    <div class="card">
      <div class="table-responsive">
        <table class="table table-striped mb-0">
          <thead>
            <tr>
              <th>ID</th>
              <th>Status</th>
              <th>Total</th>
              <th>Processado</th>
              <th>Falhas</th>
              <th>Arquivo</th>
              <th>Atualizado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="i in imports" :key="i.id">
              <td>{{ i.id }}</td>
              <td>{{ formatStatus(i.status) }}</td>
              <td>{{ i.total_rows }}</td>
              <td>{{ i.processed_rows }}</td>
              <td>{{ i.failed_rows }}</td>
              <td>{{ i.filename }}</td>
              <td>{{ i.updated_at }}</td>
            </tr>
            <tr v-if="imports.length === 0">
              <td colspan="7" class="text-center py-4">Nenhuma importação</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</template>

<script>
import { api } from './api'

export default {
  name: 'App',
  data() {
    return {
      distances: [],
      imports: [],
      saving: false,
      importing: false,
      modeRoad: false,
      formError: '',
      importError: '',
      form: {
        cep_origin: '',
        cep_destination: '',
      },
      file: null,
    }
  },
  mounted() {
    this.loadDistances()
    this.loadImports()
  },
  methods: {
    formatKm(v) {
      if (v === null || v === undefined) return ''
      return String(v).replace('.', ',')
    },
    formatCep(v) {
      if (v.length !== 8) return v
      return v.slice(0, 5) + '-' + v.slice(5)
    },
    formatStatus(v) {
      switch (v) {
        case 'queued':
          return 'Em fila'
        case 'processing':
          return 'Processando'
        case 'completed':
          return 'Concluído'
        case 'failed':
          return 'Falhou'
        default:
          return v
      }
    },
    onFileChange(e) {
      this.file = e.target.files && e.target.files[0] ? e.target.files[0] : null
    },
    async loadDistances() {
      const res = await api.get('/distances')
      res.data.data.forEach(d => {
        d.created_at = new Date(d.created_at).toLocaleString()
      })
      this.distances = res.data.data || []
    },
    async loadImports() {
      const res = await api.get('/imports')
      res.data.data.forEach(i => {
        i.updated_at = new Date(i.updated_at).toLocaleString()
      })
      this.imports = res.data.data || []
    },
    async createDistance() {
      this.formError = ''
      this.saving = true
      try {
        if(this.form.cep_origin.length !== 8 || this.form.cep_destination.length !== 8) {
          throw new Error('Os CEPs devem conter exatamente 8 dígitos')
        }
        const payload = {
          cep_origin: this.form.cep_origin,
          cep_destination: this.form.cep_destination,
          mode: this.modeRoad ? 'road' : 'haversine',
        }
        await api.post('/distances', payload)
        this.form.cep_origin = ''
        this.form.cep_destination = ''
        await this.loadDistances()
      } catch (e) {
        this.formError = e.response && e.response.data && e.response.data.message ? e.response.data.message : 'Erro ao criar'
      } finally {
        this.saving = false
      }
    },
    async uploadCsv() {
      this.importError = ''
      this.importing = true
      try {
        const fd = new FormData()
        fd.append('file', this.file)
        await api.post('/imports', fd)
        this.file = null
        await this.loadImports()
      } catch (e) {
        this.importError = e.response && e.response.data && e.response.data.message ? e.response.data.message : 'Erro ao enviar CSV'
      } finally {
        this.importing = false
      }
    },
  },
}
</script>
