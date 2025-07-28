# Course Tree Structure Implementation Guide

## Overview

This document outlines the implementation of a flexible tree-based course structure in our LMS platform. The tree structure allows for more intuitive content organization without strict module/chapter dependencies.

## Backend Implementation

### Database Changes

1. **Added tree structure fields to the courses table**:
   - `parent_id` - References another course (parent course, module, etc.)
   - `content_type` - Defines the type of content (course, module, chapter)
   - `position` - For ordering within the same level
   - Additional content fields for different content types

2. **Dropped legacy tables**:
   - Removed separate modules and chapters tables
   - All content is now stored in the courses table

3. **Migration Cleanup**:
   - Fixed duplicate migrations
   - Marked pending migrations as already run

### Model Changes

1. **Updated Course model with tree relationships**:
   ```php
   // Parent relationship
   public function parent(): BelongsTo
   {
       return $this->belongsTo(Course::class, 'parent_id');
   }
   
   // Children relationship (modules, chapters, etc.)
   public function children(): HasMany
   {
       return $this->hasMany(Course::class, 'parent_id')->orderBy('position');
   }
   
   // Get only modules (first level children)
   public function modules(): HasMany
   {
       return $this->hasMany(Course::class, 'parent_id')
                   ->where('content_type', 'module')
                   ->orderBy('position');
   }
   
   // Get only chapters
   public function chapters(): HasMany
   {
       return $this->hasMany(Course::class, 'parent_id')
                   ->where('content_type', 'chapter')
                   ->orderBy('position');
   }
   
   // Recursive method to get entire course tree
   public function getTree()
   {
       return $this->children()->with(['children' => function($query) {
           $query->orderBy('position');
       }])->orderBy('position')->get();
   }
   ```

### Controller Updates

**CourseBuilderController** was updated to work with the tree structure:

1. **getCourseStructure**: Retrieves the hierarchical course structure
2. **createModule**: Creates a new Course record with content_type='module'
3. **createChapter**: Creates a new Course record with content_type='chapter'
4. **reorderContent**: Updates position and parent_id fields

### API Endpoints

The API endpoints remained consistent for backward compatibility:

- `GET /api/v1/courses/{courseId}/structure` - Get the course structure
- `POST /api/v1/courses/{courseId}/modules` - Create a new module
- `PUT /api/v1/modules/{moduleId}` - Update a module
- `DELETE /api/v1/modules/{moduleId}` - Delete a module and its chapters
- `POST /api/v1/courses/{courseId}/modules/{moduleId}/chapters` - Create a chapter
- `PUT /api/v1/chapters/{chapterId}` - Update a chapter
- `DELETE /api/v1/chapters/{chapterId}` - Delete a chapter
- `POST /api/v1/courses/{courseId}/reorder` - Reorder modules and chapters

## Frontend Implementation

We've created a new tree-based course builder in the `course-builder-new` folder with the following components:

### Context and State Management

```tsx
// CourseBuilderContext.tsx
export interface CourseContent {
  id: string;
  title: string;
  description?: string;
  position: number;
  content_type: 'course' | 'module' | 'chapter' | 'lesson';
  parent_id?: string;
  content?: string;
  video_url?: string;
  learning_objectives?: string[];
  duration_minutes?: number;
  duration_hours?: number;
  children?: CourseContent[];
}

// Key context methods
const CourseBuilderContext = createContext<CourseBuilderContextType>({
  // State and methods for managing course data
  courseId,
  setCourseId,
  courseDetails,
  updateCourseDetails,
  structure,
  loadStructure,
  pricing,
  loadPricing,
  saveCourseDetails,
  savePricing,
  addContent,
  updateContent,
  deleteContent,
  reorderContent,
  publishCourse,
  loading
});
```

### Main Components

1. **TreeCourseBuilder**: Main container with tabs for course building steps
   ```tsx
   export function TreeCourseBuilder({ courseId }: CourseBuilderProps) {
     const [activeTab, setActiveTab] = useState('details');
   
     return (
       <CourseBuilderProvider initialCourseId={courseId || null}>
         <Tabs value={activeTab} onValueChange={setActiveTab}>
           <TabsList>
             <TabsTrigger value="details">Course Details</TabsTrigger>
             <TabsTrigger value="structure">Structure</TabsTrigger>
             <TabsTrigger value="pricing">Pricing</TabsTrigger>
             <TabsTrigger value="publish">Publish</TabsTrigger>
           </TabsList>
           
           <TabsContent value="details">
             <CourseDetails onComplete={() => setActiveTab('structure')} />
           </TabsContent>
           
           {/* Other tab contents */}
         </Tabs>
       </CourseBuilderProvider>
     );
   }
   ```

2. **CourseStructure**: Tree-based content editor with drag-and-drop
   - Uses drag-and-drop for reordering content
   - Supports nested content structure
   - Provides forms for content editing

3. **CoursePricing**: Pricing model configuration
   - One-time purchase
   - Monthly subscription
   - Full curriculum access
   - Discount management
   - International pricing

4. **CoursePublish**: Publishing workflow
   - Requirements checklist
   - Course summary
   - Publishing guidelines

### Key Features

1. **Drag and Drop Reordering**
   ```tsx
   const onDragEnd = async (result: DropResult) => {
     // Handle module reordering
     if (type === 'module') {
       const reorderedItems = Array.from(structure?.modules || []);
       const [removed] = reorderedItems.splice(source.index, 1);
       reorderedItems.splice(destination.index, 0, removed);
       
       const updatedItems = reorderedItems.map((item, index) => ({
         id: item.id,
         position: index,
         parent_id: courseId as string
       }));
       
       await reorderContent(updatedItems);
     }
     
     // Handle chapter reordering (within or between modules)
     // ...
   };
   ```

2. **Content Type Management**
   ```tsx
   const addContent = async (content: Partial<CourseContent>, parentId?: string): Promise<boolean> => {
     if (!courseId) {
       toast.error('Save course details first');
       return false;
     }
     
     const contentType = content.content_type;
     let endpoint = '';
     
     if (contentType === 'module') {
       endpoint = `/api/v1/courses/${courseId}/modules`;
     } else if (contentType === 'chapter' && parentId) {
       endpoint = `/api/v1/courses/${courseId}/modules/${parentId}/chapters`;
     }
     
     await axios.post(endpoint, content);
     // ...
   };
   ```

3. **Flexible Pricing Models**
   ```tsx
   const savePricing = async (data: Partial<PricingInfo>): Promise<boolean> => {
     // Save pricing details with selected model
     await axios.put(`/api/v1/courses/${courseId}/pricing`, data);
     // ...
   };
   ```

## Benefits of Tree Structure

1. **Flexibility**: 
   - Create any depth of content hierarchy
   - No restrictions on structure (can have chapters directly under courses)
   - Easier to adapt to different teaching styles

2. **Simplified Data Model**:
   - Single table for all content types
   - Consistent relationships and queries
   - Easier to maintain and extend

3. **Enhanced Organization**:
   - Drag-and-drop reordering
   - Visual hierarchy in UI
   - Position tracking for consistent ordering

4. **Future Extensibility**:
   - Easy to add new content types (lessons, sections, units)
   - Can implement content reuse across courses
   - Support for complex course structures

## Usage Guide

### Creating a Course with Tree Structure

1. **Basic Details**:
   - Set course title, description, category
   - Upload thumbnail

2. **Building Structure**:
   - Add modules as top-level containers
   - Add chapters within modules
   - Use drag-and-drop to arrange content
   - Set content details like videos and learning objectives

3. **Setting Pricing**:
   - Choose pricing model
   - Set base price and optional discounts
   - Configure subscription options if applicable

4. **Publishing**:
   - Review course structure and requirements
   - Publish when ready

### API Integration Examples

**Getting course structure**:
```javascript
const fetchCourseStructure = async (courseId) => {
  const response = await axios.get(`/api/v1/courses/${courseId}/structure`);
  return response.data;
};
```

**Creating a module**:
```javascript
const createModule = async (courseId, moduleData) => {
  const response = await axios.post(`/api/v1/courses/${courseId}/modules`, {
    title: moduleData.title,
    description: moduleData.description,
    position: moduleData.position
  });
  return response.data;
};
```

**Creating a chapter**:
```javascript
const createChapter = async (courseId, moduleId, chapterData) => {
  const response = await axios.post(
    `/api/v1/courses/${courseId}/modules/${moduleId}/chapters`, 
    {
      title: chapterData.title,
      description: chapterData.description,
      content: chapterData.content,
      video_url: chapterData.videoUrl,
      position: chapterData.position
    }
  );
  return response.data;
};
```

**Reordering content**:
```javascript
const reorderContent = async (courseId, items) => {
  const response = await axios.post(`/api/v1/courses/${courseId}/reorder`, {
    items: items.map((item, index) => ({
      id: item.id,
      position: index,
      parent_id: item.parentId
    }))
  });
  return response.data;
};
```

## Implementation Roadmap

1. ✅ **Database Migration**: Add tree structure fields to courses table
2. ✅ **Model Updates**: Add tree relationships to Course model
3. ✅ **API Updates**: Adapt CourseBuilderController to use tree structure
4. ✅ **Frontend Components**: Create new tree-based course builder
5. ✅ **Migration Cleanup**: Remove duplicate migrations

## Next Steps

1. **Data Migration**: Transfer existing course data to the tree structure
2. **Frontend Integration**: Connect the new course builder to routes
3. **Testing**: Comprehensive testing of all features
4. **Documentation**: Update API documentation for developers
5. **Analytics**: Adapt analytics to work with the new structure

## Conclusion

The tree structure implementation provides a more flexible and intuitive way to organize course content. It simplifies the data model while expanding capabilities, enabling instructors to create courses with custom structures that best fit their teaching style and content.
