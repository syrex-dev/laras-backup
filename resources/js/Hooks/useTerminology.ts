import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

export function useTerminology() {
    const { props } = usePage<PageProps>();
    const terminology = props.institution?.terminology_dict || {};

    /**
     * Mengambil istilah dinamis sesuai key konfigurasi profil instansi.
     * Contoh: term('unit_level_1', 'Jurusan') -> 'Jurusan' atau 'Bagian'
     *
     * @param key Key kamus istilah
     * @param fallback Nilai bawaan bila belum diatur
     */
    const term = (key: string, fallback: string = ''): string => {
        return terminology[key] || fallback;
    };

    return {
        term,
        terminology,
    };
}
