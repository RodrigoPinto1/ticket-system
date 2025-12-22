export function useRoute() {
  return (name: string, params?: any) => (window as any).route(name, params)
}
