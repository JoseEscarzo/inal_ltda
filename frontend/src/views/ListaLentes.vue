<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

// 1. Creamos una variable reactiva para guardar los lentes
const lentes = ref([])

// 2. Función para pedir los datos a Laravel
const obtenerLentes = async () => {
    try {
        // Nota: Usamos la URL completa de tu API de Laravel
        const response = await axios.get('http://localhost:8000/api/lentes')
        lentes.value = response.data
    } catch (error) {

    }
}

// 3. Ejecutamos la función cuando el componente se cargue
onMounted(() => {
    obtenerLentes()
})
</script>

<template>
    <div>
        <h1>Catálogo de Lentes</h1>

        <ul v-if="lentes.length > 0">
            <li v-for="lente in lentes" :key="lente.id">
                <strong>{{ lente.marca }}</strong> - ${{ lente.precio }}
            </li>
        </ul>

        <p v-else>Cargando lentes...</p>
    </div>
</template>
