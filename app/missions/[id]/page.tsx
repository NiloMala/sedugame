import { redirect } from 'next/navigation';
export default function MissionPage({params}:{params:{id:string}}){redirect(`/play/${params.id}`)}
