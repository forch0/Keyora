import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { PasswordGenerator } from '@/features/tools/PasswordGenerator'
import { PasswordStrengthChecker } from '@/features/tools/PasswordStrengthChecker'

export function ToolsPage() {
  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold">Password Tools</h1>

      <Tabs defaultValue="generator">
        <TabsList>
          <TabsTrigger value="generator">Generator</TabsTrigger>
          <TabsTrigger value="strength">Strength Checker</TabsTrigger>
        </TabsList>

        <TabsContent value="generator" className="mt-4">
          <div className="max-w-2xl">
            <PasswordGenerator />
          </div>
        </TabsContent>

        <TabsContent value="strength" className="mt-4">
          <div className="max-w-2xl">
            <PasswordStrengthChecker />
          </div>
        </TabsContent>
      </Tabs>
    </div>
  )
}
