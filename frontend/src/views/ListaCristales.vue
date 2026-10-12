<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const cristales = ref([])

const obtenerCristales = async () => {
    try{
        const response = await axios.get('http://localhost:8000/api/cristales')
        cristales.value = response.data;
    }catch (error){
        console.error("Hubo un error al pedir los lentes:", error)
    }
}

onMounted(() => {
    obtenerCristales()
})
</script>

<template>
    <div>
        <h1>Catalogo Cristales</h1>

        <ul v-if="cristales.length > 0">
            <li v-for="cristal in cristales" :key="cristales.id">
                <strong>{{ cristal.tipo }}</strong> - {{ cristal.precio }} Bs.

            </li>
        </ul>
    </div>
</template>